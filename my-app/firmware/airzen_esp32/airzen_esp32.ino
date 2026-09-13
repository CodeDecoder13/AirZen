// AirZen sensor node. Reads DHT11 (temp/humidity), MQ-7 (CO), MQ-135 (NOx),
// and a Keyestudio GP2Y1014AU dust sensor (PM2.5), then POSTs a JSON payload
// to the AirZen ingestion API every 10 seconds.

#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>

// ---- Configuration (edit these before flashing) ----
const char* WIFI_SSID = "YOUR_WIFI_SSID";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";
const char* SERVER_URL = "http://YOUR_SERVER_HOST/api/readings"; // e.g. https://airzen.up.railway.app/api/readings
const char* DEVICE_KEY = "change-me-to-a-long-random-string"; // must match DEVICE_API_KEY on the server
const char* DEVICE_ID = "esp32-room-204";

// ---- Pins ----
#define DHTPIN 4
#define DHTTYPE DHT11
#define MQ7_PIN 34     // CO, analog
#define MQ135_PIN 35   // NOx, analog
#define DUST_PIN 32    // PM2.5, analog (GP2Y1014AU)

DHT dht(DHTPIN, DHTTYPE);

const unsigned long POST_INTERVAL_MS = 10000;
unsigned long lastPostAt = 0;

void connectWiFi() {
  Serial.print("Connecting to WiFi: ");
  Serial.println(WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.print("WiFi connected, IP: ");
  Serial.println(WiFi.localIP());
}

// Converts a raw MQ-7 analog reading to an approximate CO ppm value.
// Calibration curve derived from the MQ-7 datasheet Rs/Ro vs ppm chart;
// replace the constants below after calibrating against a known CO source.
float readCoPpm() {
  int raw = analogRead(MQ7_PIN);
  float voltage = raw * (3.3 / 4095.0);
  float ppm = voltage * 30.0; // placeholder linear approximation - calibrate per datasheet
  return ppm;
}

// Converts a raw MQ-135 analog reading to an approximate NOx index value.
// Same caveat as readCoPpm(): replace with the datasheet's Rs/Ro curve once
// calibrated against a reference gas source.
float readNitrogenIndex() {
  int raw = analogRead(MQ135_PIN);
  float voltage = raw * (3.3 / 4095.0);
  float index = voltage * 60.0; // placeholder linear approximation - calibrate per datasheet
  return index;
}

// Converts a raw GP2Y1014AU analog reading to an approximate PM2.5 ug/m3
// value, using the linear approximation from the Sharp/Keyestudio datasheet:
// Voltage (mV) = 0.17 * dust_density (ug/m3) + 0.6
float readPm25() {
  int raw = analogRead(DUST_PIN);
  float voltage = raw * (3300.0 / 4095.0); // millivolts
  float density = (voltage - 600.0) / 0.17;
  return density < 0 ? 0.0 : density;
}

void postReading(float temperature, float humidity, float co, float nitrogen, float pm25) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi dropped, reconnecting before POST...");
    connectWiFi();
  }

  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Key", DEVICE_KEY);

  String payload = String("{") +
    "\"temperature\":" + String(temperature, 1) + "," +
    "\"humidity\":" + String(humidity, 1) + "," +
    "\"co\":" + String(co, 2) + "," +
    "\"nitrogen\":" + String(nitrogen, 1) + "," +
    "\"pm25\":" + String(pm25, 1) + "," +
    "\"device_id\":\"" + String(DEVICE_ID) + "\"" +
    "}";

  Serial.print("POST payload: ");
  Serial.println(payload);

  int statusCode = http.POST(payload);
  Serial.print("Server responded with status: ");
  Serial.println(statusCode);

  if (statusCode > 0) {
    Serial.println(http.getString());
  }

  http.end();
}

void setup() {
  Serial.begin(115200);
  dht.begin();
  connectWiFi();
}

void loop() {
  if (millis() - lastPostAt >= POST_INTERVAL_MS) {
    lastPostAt = millis();

    float temperature = dht.readTemperature();
    float humidity = dht.readHumidity();

    if (isnan(temperature) || isnan(humidity)) {
      Serial.println("Failed to read from DHT11, skipping this cycle.");
      return;
    }

    float co = readCoPpm();
    float nitrogen = readNitrogenIndex();
    float pm25 = readPm25();

    postReading(temperature, humidity, co, nitrogen, pm25);
  }
}
