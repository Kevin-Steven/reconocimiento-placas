import cv2
import numpy as np
import pytesseract
from PIL import Image, ImageFont, ImageDraw
import mysql.connector
from mysql.connector import Error
from datetime import datetime, timedelta
import tkinter as tk
from tkinter import ttk
import threading

# Conectar a la base de datos MySQL
def connect_to_db():
    try:
        connection = mysql.connector.connect(
            host='localhost',
            port=3308,
            database='placas_db',
            user='root',
            password=''
        )
        if connection.is_connected():
            return connection
    except Error as e:
        print("Error al conectar a la base de datos MySQL", e)
        return None

# Verificar si la placa está en la base de datos
def verificar_placa(placa, connection):
    cursor = connection.cursor()
    query = "SELECT * FROM placas WHERE placa = %s ORDER BY id DESC LIMIT 1"
    cursor.execute(query, (placa,))
    result = cursor.fetchone()
    cursor.fetchall()  # Leer todos los resultados
    cursor.close()
    if result:
        # Restablecer el tiempo de inicio de denegación cada vez que una placa válida es encontrada
        global tiempo_inicio_denegacion
        tiempo_inicio_denegacion = None
    return result

# Permitir acceso registrando un nuevo ingreso
def permitir_acceso(placa_id, connection):
    cursor = connection.cursor()
    query = "INSERT INTO ingresos (placa_id, fecha_ingreso) VALUES (%s, %s)"
    cursor.execute(query, (placa_id, datetime.now()))
    connection.commit()
    cursor.close()
    print("Acceso permitido para la placa ID:", placa_id)
    # arduino.write(b'E')  # Enviar comando de apertura de entrada a Arduino 

# Registrar la salida de una placa
def registrar_salida(ingreso_id, connection):
    cursor = connection.cursor()
    query = "INSERT INTO salidas (ingreso_id, fecha_salida) VALUES (%s, %s)"
    cursor.execute(query, (ingreso_id, datetime.now()))
    connection.commit()
    cursor.close()
    print("Salida registrada para el ingreso ID:", ingreso_id)
    # arduino.write(b'S')  # Enviar comando de apertura de salida a Arduino

# Denegar el acceso a una placa
def denegar_acceso(placa, connection):
    cursor = connection.cursor()
    query = "INSERT INTO placas_no_registradas (placa, fecha_denegado) VALUES (%s, %s)"
    cursor.execute(query, (placa, datetime.now()))
    connection.commit()
    cursor.close()
    print("Acceso denegado para la placa:", placa)
    # arduino.write(b'D')  # Enviar comando de denegación de acceso a Arduino

# Obtener la lista de placas con acceso permitido hoy, incluyendo la fecha de salida
def obtener_lista_acceso_hoy(connection):
    cursor = connection.cursor()
    query = """
        SELECT p.placa,
               DATE(i.fecha_ingreso) AS fecha_ingreso,
               TIME(i.fecha_ingreso) AS hora_ingreso,
               DATE(s.fecha_salida) AS fecha_salida,
               TIME(s.fecha_salida) AS hora_salida
        FROM placas p
        LEFT JOIN ingresos i ON p.id = i.placa_id
        LEFT JOIN salidas s ON i.id = s.ingreso_id
        WHERE DATE(i.fecha_ingreso) = CURDATE()
        ORDER BY i.fecha_ingreso ASC
    """
    cursor.execute(query)
    resultados = cursor.fetchall()
    cursor.close()
    return resultados

# Mostrar lista de accesos permitidos en una nueva ventana
def mostrar_lista_accesos():
    lista_accesos = obtener_lista_acceso_hoy(db_connection)
    lista_placas = [(placa[0], 
                     placa[1], 
                     placa[2], 
                     placa[3], 
                     placa[4]) 
                    for placa in lista_accesos]

    window = tk.Toplevel(root)
    window.title("Lista de Accesos Permitidos")
    window.configure(bg='#000000')  # Fondo negro
    window.geometry("1000x400")

    style = ttk.Style(window)
    style.theme_use('clam')

    style.configure("Treeview",
                    background="#000000",  # Fondo negro
                    foreground="#FFFFFF",  # Texto blanco
                    rowheight=25,
                    fieldbackground="#000000",  # Fondo negro
                    font=("Roboto", 12))
    style.configure("Treeview.Heading",
                    background="#000000",  # Fondo negro
                    foreground="#FFFFFF",  # Texto blanco
                    font=("Roboto", 14, "bold"))
    style.map('Treeview', background=[('selected', '#1DA1F2')], foreground=[('selected', '#FFFFFF')])  # Azul y Blanco

    tree = ttk.Treeview(window, columns=('Placa', 'Fecha de Ingreso', 'Hora de Ingreso', 'Fecha de Salida', 'Hora de Salida'), show='headings', style="Treeview")
    tree.heading('Placa', text='Placa')
    tree.heading('Fecha de Ingreso', text='Fecha de Ingreso')
    tree.heading('Hora de Ingreso', text='Hora de Ingreso')
    tree.heading('Fecha de Salida', text='Fecha de Salida')
    tree.heading('Hora de Salida', text='Hora de Salida')
    tree.pack(fill=tk.BOTH, expand=True)

    for placa in lista_placas:
        tree.insert('', tk.END, values=(placa[0], placa[1], placa[2], placa[3], placa[4]))

    scrollbar = ttk.Scrollbar(window, orient=tk.VERTICAL, command=tree.yview)
    tree.configure(yscroll=scrollbar.set)
    scrollbar.pack(side=tk.RIGHT, fill=tk.Y)

# Crear la ventana principal y ocultarla
root = tk.Tk()
root.withdraw()

# Bandera de control para terminar el bucle de actualización
running = True

# Inicializar variables globales
Ctexto = ''
mensaje = ''
boton_acceso = False
boton_salida = False
boton_denegar = False
procesamiento_activo = True  # Nueva bandera para controlar el procesamiento
tiempo_espera = datetime.now()  # Tiempo de espera para reanudar el procesamiento
temporizador = None  # Temporizador para las acciones automáticas
tiempo_inicio_procesamiento = None  # Tiempo de inicio del procesamiento
tiempo_inicio_denegacion = None  # Tiempo de inicio para denegación automática

# Función para permitir acceso automáticamente
def permitir_acceso_automatico():
    global boton_acceso, procesamiento_activo, tiempo_espera, mensaje
    if boton_acceso:
        placa_id = verificar_placa(Ctexto, db_connection)[0]
        permitir_acceso(placa_id, db_connection)
        mensaje = "Acceso permitido"
        boton_acceso = False
        procesamiento_activo = False
        tiempo_espera = datetime.now() + timedelta(seconds=10)

# Función para registrar salida automáticamente
def registrar_salida_automatica():
    global boton_salida, procesamiento_activo, tiempo_espera, mensaje
    if boton_salida:
        placa_id = verificar_placa(Ctexto, db_connection)[0]
        # Obtener el último ingreso sin salida
        query = """
            SELECT i.id FROM ingresos i
            LEFT JOIN salidas s ON i.id = s.ingreso_id
            WHERE i.placa_id = %s AND s.id IS NULL
            ORDER BY i.fecha_ingreso DESC LIMIT 1
        """
        cursor = db_connection.cursor()
        cursor.execute(query, (placa_id,))
        ingreso = cursor.fetchone()
        cursor.close()
        if ingreso and ingreso[0]:
            ingreso_id = ingreso[0]
            registrar_salida(ingreso_id, db_connection)
            mensaje = "Salida registrada"
        else:
            mensaje = "No se encontró ingreso sin salida"
        boton_salida = False
        procesamiento_activo = False
        tiempo_espera = datetime.now() + timedelta(seconds=10)

# Función para denegar acceso automáticamente
def denegar_acceso_automatico():
    global boton_denegar, procesamiento_activo, mensaje, Ctexto, tiempo_inicio_denegacion, tiempo_espera, tiempo_inicio_procesamiento
    if boton_denegar and tiempo_inicio_denegacion and (datetime.now() - tiempo_inicio_denegacion >= timedelta(seconds=20)):
        denegar_acceso(Ctexto, db_connection)
        mensaje = "Acceso denegado"
        Ctexto = 'SIN RESULTADOS'  
        boton_denegar = False
        procesamiento_activo = False
        tiempo_espera = datetime.now() + timedelta(seconds=10)  # Pausa de 10 segundos
        tiempo_inicio_procesamiento = None  # Reiniciar el temporizador de procesamiento
        tiempo_inicio_denegacion = None  # Reiniciar el temporizador de denegación

# Función para actualizar OpenCV y Tkinter
def update():
    global running, Ctexto, mensaje, boton_acceso, boton_salida, boton_denegar, procesamiento_activo, tiempo_espera, temporizador, tiempo_inicio_procesamiento, tiempo_inicio_denegacion
    if not running:
        root.quit()  # Termina el mainloop de Tkinter
        return
    
    ret, frame = cap.read()

    if ret:
        # Redimensionar el frame para ocupar toda la pantalla
        frame = cv2.resize(frame, (1280, 720))

        # Crear una imagen PIL para poder dibujar texto con la fuente Roboto
        pil_img = Image.fromarray(cv2.cvtColor(frame, cv2.COLOR_BGR2RGB))
        draw = ImageDraw.Draw(pil_img)

        # Dibujar área para la placa detectada
        draw.rectangle((50, 50, 600, 150), fill="#1E1E1E")  # Gris Oscuro
        draw.text((60, 60), Ctexto, font=font_large, fill="#FFFFFF")  # Blanco
        draw.text((60, 120), mensaje, font=font_small, fill="#FFFFFF")  # Blanco

        # Comprobar si el tiempo de espera ha pasado
        if datetime.now() >= tiempo_espera:
            procesamiento_activo = True  # Permitir procesamiento después del tiempo de espera

        if procesamiento_activo:
            if tiempo_inicio_procesamiento is None:
                tiempo_inicio_procesamiento = datetime.now()

            # Extraemos el ancho y el alto de los fotogramas
            al, an, c = frame.shape

            # Tomar el centro de la imagen
            x1 = int(an / 3)  # Tomamos 1/3 de la imagen
            x2 = int(x1 * 2)  # Hasta el inicio del 3/3 de la imagen

            y1 = int(al / 3)  # Tomamos el 1/3 de la imagen
            y2 = int(y1 * 2)  # Hasta el inicio 3/3 de la imagen

            # Ubicamos el rectángulo en las zonas extraídas
            draw.rectangle((x1, y1, x2, y2), outline="#1DA1F2", width=2)  # Azul 

            # Realizamos un recorte a nuestra zona de interés
            recorte = frame[y1:y2, x1:x2]

            # Preprocesamiento de la zona de interés
            gray = cv2.cvtColor(recorte, cv2.COLOR_BGR2GRAY)
            gray = cv2.bilateralFilter(gray, 11, 17, 17)
            edged = cv2.Canny(gray, 30, 200)

            # Extraemos los contornos de la zona seleccionada
            contornos, _ = cv2.findContours(edged, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)

            # Primero los ordenamos del más grande al más pequeño
            contornos = sorted(contornos, key=lambda x: cv2.contourArea(x), reverse=True)[:10]

            # Inicializar bin_placa
            bin_placa = None

            # Dibujamos los contornos extraídos
            for contorno in contornos:
                area = cv2.contourArea(contorno)
                if 500 < area < 50000:
                    # Detectamos la placa
                    x, y, ancho, alto = cv2.boundingRect(contorno)

                    # Extraemos las coordenadas - Absolutas del Rectángulo
                    xpi = x + x1        # Coordenada de la placa en X inicial
                    ypi = y + y1        # Coordenada de la placa en Y inicial

                    xpf = x + ancho + x1 # Coordenada de la placa en X final
                    ypf = y + alto + y1  # Coordenada de la placa en Y final

                    # Dibujamos el rectángulo
                    draw.rectangle((xpi, ypi, xpf, ypf), outline="#00E8FF", width=2)  # Azul 

                    # Extraemos los píxeles
                    placa = frame[ypi:ypf, xpi:xpf]

                    # Convertimos a escala de grises y binarizamos
                    gray_placa = cv2.cvtColor(placa, cv2.COLOR_BGR2GRAY)
                    _, bin_placa = cv2.threshold(gray_placa, 128, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)

                    # Convertimos la imagen binarizada a un formato compatible con PIL
                    bin_placa_pil = Image.fromarray(bin_placa)
                    bin_placa_pil = bin_placa_pil.convert("L")

                    # Nos aseguramos de tener un buen tamaño de placa
                    if bin_placa_pil.size[1] >= 36 and bin_placa_pil.size[0] >= 82:
                        # Declaramos la dirección de Pytesseract
                        pytesseract.pytesseract.tesseract_cmd = r'C:\Program Files\Tesseract-OCR\tesseract.exe'

                        # Extraemos el texto
                        config = "--psm 7"
                        texto = pytesseract.image_to_string(bin_placa_pil, config=config)

                        # If para no mostrar basura y asegurar que todo el texto esté en mayúsculas
                        if len(texto) >= 7 and texto.isupper():
                            Ctexto = texto.strip()

                            # Verificar la placa en la base de datos
                            result = verificar_placa(Ctexto, db_connection)
                            if result:
                                placa_id = result[0]
                                # Obtener el último ingreso de esta placa
                                query_ingreso = "SELECT id, fecha_ingreso FROM ingresos WHERE placa_id = %s ORDER BY fecha_ingreso DESC LIMIT 1"
                                cursor = db_connection.cursor()
                                cursor.execute(query_ingreso, (placa_id,))
                                ingreso = cursor.fetchone()
                                cursor.close()
                                if ingreso and ingreso[0] is not None:
                                    ingreso_id = ingreso[0]
                                    # Verificar si hay una salida para este ingreso
                                    query_salida = "SELECT fecha_salida FROM salidas WHERE ingreso_id = %s"
                                    cursor = db_connection.cursor()
                                    cursor.execute(query_salida, (ingreso_id,))
                                    salida = cursor.fetchone()
                                    cursor.close()
                                    if salida and salida[0] is not None:
                                        # El vehículo no está en el estacionamiento, permitir ingreso
                                        mensaje = "Tiene acceso"
                                        boton_acceso = True
                                        boton_salida = False
                                        boton_denegar = False
                                    else:
                                        # El vehículo está en el estacionamiento, registrar salida
                                        mensaje = "Placa en el sistema - acceso permitido"
                                        boton_salida = True
                                        boton_acceso = False
                                        boton_denegar = False
                                else:
                                    # No hay ingreso previo, permitir ingreso
                                    mensaje = "Tiene acceso"
                                    boton_acceso = True
                                    boton_salida = False
                                    boton_denegar = False
                                procesamiento_activo = False  # Detener procesamiento
                                tiempo_espera = datetime.now() + timedelta(seconds=10)  # Tiempo de espera de 10 segundos

                                if temporizador:
                                    root.after_cancel(temporizador)
                                temporizador = root.after(3000, permitir_acceso_automatico if boton_acceso else registrar_salida_automatica)
                            else:
                                mensaje = "La placa no está registrada"
                                boton_denegar = True
                                boton_acceso = False
                                boton_salida = False
                                if temporizador:
                                    root.after_cancel(temporizador)
                                if tiempo_inicio_denegacion is None:
                                    tiempo_inicio_denegacion = datetime.now()
                        else:
                            procesamiento_activo = True  # Continuar procesamiento si no se detecta placa válida

                    break

        # Mostrar botones
        if boton_acceso:
            draw.rectangle((50, 200, 300, 250), fill="#004B8D")  # Azul 
            draw.text((60, 210), "Permitir acceso (A)", font=font_small, fill="#FFFFFF")  # Blanco
        if boton_salida:
            draw.rectangle((50, 200, 300, 250), fill="#004B8D")  # Azul 
            draw.text((60, 210), "Registrar salida (S)", font=font_small, fill="#FFFFFF")  # Blanco
        if boton_denegar:
            draw.rectangle((50, 200, 300, 250), fill="#FF0000")  # Rojo
            draw.text((60, 210), "Denegar acceso (D)", font=font_small, fill="#FFFFFF")  # Blanco

        # Mostrar botón para ver la lista de accesos permitidos
        draw.rectangle((50, 270, 400, 320), fill="#004B8D")  # Azul
        draw.text((60, 280), "Ver accesos permitidos (L)", font=font_small, fill="#FFFFFF")  # Blanco

        # Convertir de nuevo a OpenCV
        frame = cv2.cvtColor(np.array(pil_img), cv2.COLOR_RGB2BGR)

        # Mostrar el frame principal
        cv2.imshow("CONTROL DE ACCESO VEHICULAR", frame)

        # Detectar clic en los botones
        key = cv2.waitKey(1)
        if key == 27:  # Esc para salir
            running = False
        elif key == ord('l') or key == ord('L'):  # L para mostrar lista de accesos
            mostrar_lista_accesos()

        # Llamar a la función de denegación automática si corresponde
        denegar_acceso_automatico()

    root.after(10, update)  # Programar la próxima llamada de actualización

# Función para cerrar adecuadamente la aplicación
def cerrar_app():
    global running
    running = False
    root.quit()

# Conectar a la base de datos
db_connection = connect_to_db()
if not db_connection:
    print("No se pudo conectar a la base de datos. Saliendo...")
    exit()

# Cargar la fuente Roboto
font_path = "Reconocimiento_placas/sources/Roboto-Regular.ttf"
font_large = ImageFont.truetype(font_path, 48)
font_small = ImageFont.truetype(font_path, 24)

# Realizar videoCaptura
cap = cv2.VideoCapture(0)

# Llamar a cerrar_app al cerrar la ventana principal
root.protocol("WM_DELETE_WINDOW", cerrar_app)

# Iniciar el ciclo de actualización
root.after(0, update)
root.mainloop()

# Cerrar la conexión a la base de datos y liberar la cámara
if db_connection.is_connected():
    db_connection.close()
cap.release()
cv2.destroyAllWindows()
