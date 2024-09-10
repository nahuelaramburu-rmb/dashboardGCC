from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import traceback
import re
import sys
import time
import urllib.request
import os
from pydub import AudioSegment
import speech_recognition as sr
from selenium.webdriver.common.keys import Keys

numero_a_consultar = sys.argv[1]

chrome_options = Options()
chrome_options.add_argument('--headless')
chrome_options.add_argument('--no-sandbox')
chrome_options.add_argument('--disable-dev-shm-usage')

driver = webdriver.Chrome(options=chrome_options)

def delay(seconds=10):
    time.sleep(seconds)

try:
    # Abre la página web
    driver.get("https://numeracionyoperadores.cnmc.es/portabilidad/movil")

    # Espera hasta que el botón de cookies sea clicable y hacer clic
    boton_cookies = WebDriverWait(driver, 20).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, ".cookie-consent-banner .v-btn--variant-elevated"))
    )
    boton_cookies.click()

    # Espera hasta que el contenedor de cookies sea invisible
    WebDriverWait(driver, 20).until(
        EC.invisibility_of_element_located((By.CSS_SELECTOR, ".cookie-consent-banner"))
    )

    # Espera a que el input sea clickeable y lo guarda en una variable
    input_element = driver.find_element(By.CSS_SELECTOR, ".v-input__control .v-field__input")

    # Verifica si el elemento está habilitado antes de intentar hacer clic
    if input_element.is_enabled():
        # Hace clic y escribe el texto
        input_element.click()
        input_element.send_keys(numero_a_consultar)
    else:
        print("El elemento de entrada no está habilitado")

    # Espera hasta que los iframes sean cargados
    WebDriverWait(driver, 20).until(
        EC.presence_of_all_elements_located((By.TAG_NAME, "iframe"))
    )

    frames = driver.find_elements(By.TAG_NAME, "iframe")
    recaptcha_control_frame = None
    recaptcha_challenge_frame = None
    print("Frames encontrados:", len(frames))

    for frame in frames:
        title = frame.get_attribute("title")
        print(f"Frame title: {title}")  # Para depurar
        if title and re.search('reCAPTCHA', title):
            recaptcha_control_frame = frame
        if title and re.search('el desafío de recaptcha caduca dentro de dos minutos', title):
            recaptcha_challenge_frame = frame

    # Verifica si se encontró el frame de control del reCAPTCHA
    if not recaptcha_control_frame:
        print("[ERR] Unable to find reCAPTCHA control frame. Aborting solver.")
        sys.exit()

    # Cambia al frame de control del reCAPTCHA y clickea el checkbox
    driver.switch_to.frame(recaptcha_control_frame)
    WebDriverWait(driver, 20).until(
        EC.element_to_be_clickable((By.CLASS_NAME, "recaptcha-checkbox-border"))
    ).click()

    # Cambia de nuevo al contenido principal
    driver.switch_to.default_content()

    # Intenta resolver el reCAPTCHA solo con el checkbox
    try:
        # Espera un momento para ver si aparece un mensaje de éxito
        WebDriverWait(driver, 5).until(
            EC.presence_of_element_located((By.CLASS_NAME, "recaptcha-success"))
        )
        print("[INFO] reCAPTCHA solved with checkbox only.")
    except:
        print("[INFO] Audio challenge might be required.")

    # Procede con el desafío de audio solo si es necesario
    if recaptcha_challenge_frame:
        driver.switch_to.frame(recaptcha_challenge_frame)

        # Intenta hacer clic en el botón de audio
        try:
            WebDriverWait(driver, 20).until(
                EC.element_to_be_clickable((By.ID, "recaptcha-audio-button"))
            ).click()

            # Obtén el archivo de audio mp3
            src = WebDriverWait(driver, 20).until(
                EC.presence_of_element_located((By.ID, "audio-source"))
            ).get_attribute("src")
            print(f"[INFO] Audio src: {src}")

            path_to_mp3 = os.path.normpath(os.path.join(os.getcwd(), "sample.mp3"))
            path_to_wav = os.path.normpath(os.path.join(os.getcwd(), "sample.wav"))

            # Descarga el archivo de audio mp3
            urllib.request.urlretrieve(src, path_to_mp3)

            # Convierte el archivo de audio de mp3 a wav
            try:
                sound = AudioSegment.from_mp3(path_to_mp3)
                sound.export(path_to_wav, format="wav")
                sample_audio = sr.AudioFile(path_to_wav)
            except Exception as e:
                print(f"[ERR] {e}")
                sys.exit(
                    "[ERR] Please run program as administrator or download ffmpeg manually, "
                    "https://blog.gregzaal.com/how-to-install-ffmpeg-on-windows/"
                )

            # Traduce el audio a texto usando Google Voice Recognition
            r = sr.Recognizer()
            with sample_audio as source:
                audio = r.record(source)
            key = r.recognize_google(audio)
            print(f"[INFO] Recaptcha Passcode: {key}")

            # Ingresa el código de audio y envíalo
            WebDriverWait(driver, 20).until(
                EC.element_to_be_clickable((By.ID, "audio-response"))
            ).send_keys(key.lower())
            driver.find_element(By.ID, "audio-response").send_keys(Keys.ENTER)

        except Exception as e:
            print("[WARN] Audio challenge was not accessible or failed. Continuing with normal flow.")

    # Vuelve a la página inicial
    driver.switch_to.default_content()

    # Clickea en el botón de aceptar
    WebDriverWait(driver, 20).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, ".v-container .v-row .v-col button"))
    ).click()

    # Obtén el texto del párrafo
    text_operator = WebDriverWait(driver, 20).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, ".v-container .v-row .v-col-lg-8 .v-card > :nth-of-type(4)"))
    )

    # Imprime el texto del párrafo
    print(f"OPERATOR:{text_operator.text}")

except Exception as e:
    print(f"Error: {e}")
    print(traceback.format_exc())

finally:
    # Cierra el navegador solo si sigue abierto
    try:
        driver.quit()
    except Exception as e:
        print(f"Error closing the browser: {e}")
