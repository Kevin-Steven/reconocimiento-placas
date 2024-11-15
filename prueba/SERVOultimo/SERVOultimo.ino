#include <Servo.h>

Servo servoentrada; 
Servo servosalida;

void setup() {
  servoentrada.attach(3);
  servosalida.attach(5);
  Serial.begin(9600);
}

void loop() {
  if (Serial.available() > 0) {
    char command = Serial.read();
    if (command == 'E') { // Comando para entrada
      // Abrir barrera de entrada
      servoentrada.write(217);  // Mueve el servomotor a la posición de apertura
      delay(5000);              // Mantiene la barrera abierta por 5 segundos
      servoentrada.write(127);  // Mueve el servomotor a la posición de cerrado
    } else if (command == 'S') { // Comando para salida
      // Abrir barrera de salida
      servosalida.write(0);      // Mueve el servomotor a la posición de apertura
      delay(5000);               // Mantiene la barrera abierta por 5 segundos
      servosalida.write(88);     // Mueve el servomotor a la posición de cerrado
    } else if (command == 'D') { // Comando para denegar acceso
      // Mantener cerrada la barrera
      servoentrada.write(127);   // Asegurar que la barrera de entrada está cerrada
      servosalida.write(88);     // Asegurar que la barrera de salida está cerrada
    }
  }
}
