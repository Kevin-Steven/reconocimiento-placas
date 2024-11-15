import serial
import time

class ArduinoControl:
    def __init__(self, port='COM3', baudrate=9600):
        self.port = port
        self.baudrate = baudrate
        self.arduino = None

    def connect(self):
        try:
            self.arduino = serial.Serial(self.port, self.baudrate)
            time.sleep(2)  
            print("Conexión con Arduino establecida.")
        except Exception as e:
            print(f"No se pudo conectar con el Arduino: {e}")

    def send_command(self, command):
        if self.arduino:
            try:
                self.arduino.write(command.encode())  
                print(f"Comando enviado al Arduino: {command}")
            except Exception as e:
                print(f"Error al enviar comando al Arduino: {e}")
        else:
            print("Arduino no está conectado.")

    def close(self):
        if self.arduino:
            self.arduino.close()
            print("Conexión con Arduino cerrada.")
