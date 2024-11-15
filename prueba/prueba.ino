#include <Servo.h>

Servo myservo;  // Crea un objeto servo para controlar un servomotor
int currentPos = 0;    // Variable para almacenar la posición actual del servo
int servoPin = 9;      // El pin donde está conectado el servomotor

void setup() {
  myservo.attach(servoPin);  // Adjunta el servomotor al pin indicado
  Serial.begin(9600);        // Inicia la comunicación serial a 9600 baudios
}

void loop() {
  if (Serial.available() > 0) {
    char state = Serial.read();  // Lee el estado desde el serial
    int newPos = (state == '1') ? 180 : 0;  // Determina la nueva posición basada en el comando recibido

    // Mueve el servomotor solo si es necesario
    if (newPos != currentPos) {
      myservo.write(newPos);
      currentPos = newPos;  // Actualiza la posición actual del servo
    }
  }
}
