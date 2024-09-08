from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
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

# Configuración del driver de Chrome
driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()))

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
        input_element.send_keys("653165226")
    else:
        print("El elemento de entrada no está habilitado")

    # Espera hasta que los iframes sean cargados
    WebDriverWait(driver, 20).until(
        EC.presence_of_all_elements_located((By.TAG_NAME, "iframe"))
    )

    frames = driver.find_elements(By.TAG_NAME, "iframe")
    recaptcha_control_frame = None
    recaptcha_challenge_frame = None
    print("Frames encontrados:", frames)
    for frame in frames:
        title = frame.get_attribute("title")
        print(f"Frame title: {title}")  # Para depurar
        if title and re.search('reCAPTCHA', title):
            recaptcha_control_frame = frame
        if title and re.search('el desafío de recaptcha caduca dentro de dos minutos', title):
            recaptcha_challenge_frame = frame

    if not (recaptcha_control_frame and recaptcha_challenge_frame):
        print("[ERR] Unable to find recaptcha. Abort solver.")
        sys.exit()
    
    # Switch to recaptcha control frame
    driver.switch_to.frame(recaptcha_control_frame)
    WebDriverWait(driver, 20).until(
        EC.element_to_be_clickable((By.CLASS_NAME, "recaptcha-checkbox-border"))
    ).click()
    
    # Switch to recaptcha audio control frame
    driver.switch_to.default_content()
    WebDriverWait(driver, 20).until(
        EC.presence_of_all_elements_located((By.TAG_NAME, "iframe"))
    )
    driver.switch_to.frame(recaptcha_challenge_frame)
    
    # Click on audio challenge
    WebDriverWait(driver, 20).until(
        EC.element_to_be_clickable((By.ID, "recaptcha-audio-button"))
    ).click()
    
    # Switch to recaptcha audio challenge frame
    driver.switch_to.default_content()
    WebDriverWait(driver, 20).until(
        EC.presence_of_all_elements_located((By.TAG_NAME, "iframe"))
    )
    driver.switch_to.frame(recaptcha_challenge_frame)
    
    # Get the mp3 audio file
    src = WebDriverWait(driver, 20).until(
        EC.presence_of_element_located((By.ID, "audio-source"))
    ).get_attribute("src")
    print(f"[INFO] Audio src: {src}")
    
    path_to_mp3 = os.path.normpath(os.path.join(os.getcwd(), "sample.mp3"))
    path_to_wav = os.path.normpath(os.path.join(os.getcwd(), "sample.wav"))
    
    # Download the mp3 audio file from the source
    urllib.request.urlretrieve(src, path_to_mp3)
    
    # Load downloaded mp3 audio file as .wav
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
    
    # Translate audio to text with Google voice recognition
    r = sr.Recognizer()
    with sample_audio as source:
        audio = r.record(source)
    key = r.recognize_google(audio)
    print(f"[INFO] Recaptcha Passcode: {key}")

    # Key in results and submit
    WebDriverWait(driver, 20).until(
        EC.element_to_be_clickable((By.ID, "audio-response"))
    ).send_keys(key.lower())
    driver.find_element(By.ID, "audio-response").send_keys(Keys.ENTER)

    # switch a pagina inicial
    driver.switch_to.default_content()

    # Clickea en el boton de aceptar
    WebDriverWait(driver, 20).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, ".v-container .v-row .v-col button"))
    ).click()

    # esperar 5 segundos
    time.sleep(5)

    ## Aca obtendria el numero

    driver.quit()

except Exception as e:
    print(f"Error: {e}")
    print(traceback.format_exc())

finally:
    # Cierra el navegador
    driver.quit()
