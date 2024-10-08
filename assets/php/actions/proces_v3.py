from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import pyautogui
import traceback
import re
import sys
import time
import urllib.request
import os
import zipfile
from pydub import AudioSegment
import speech_recognition as sr
from selenium.webdriver.common.keys import Keys
import shutil
import requests
import random

is_debug = True

PROXY_USER  = "668f064999df71e1de9e"
PROXY_PASS  = "aeb11b19f07fc035"
PROXY_HOST  = "148.251.5.30"
PROXY_PORT  = "823"
HOST_BACK = ""

if is_debug:
    HOST_BACK = "http://localhost:3000/assets/php/actions"
else:
    HOST_BACK = "https://mediumblue-oryx-397085.hostingersite.com/assets/php/actions"

manifest_json = """
{
    "version": "1.0.0",
    "manifest_version": 2,
    "name": "Chrome Proxy",
    "permissions": [
        "proxy",
        "tabs",
        "unlimitedStorage",
        "storage",
        "<all_urls>",
        "webRequest",
        "webRequestBlocking"
    ],
    "background": {
        "scripts": ["background.js"]
    },
    "minimum_chrome_version":"22.0.0"
}
"""

background_js = """
var config = {
        mode: "fixed_servers",
        rules: {
        singleProxy: {
            scheme: "http",
            host: "%s",
            port: parseInt(%s)
        },
        bypassList: ["localhost"]
        }
    };
chrome.proxy.settings.set({value: config, scope: "regular"}, function() {});
function callbackFn(details) {
    return {
        authCredentials: {
            username: "%s",
            password: "%s"
        }
    };
}
chrome.webRequest.onAuthRequired.addListener(
            callbackFn,
            {urls: ["<all_urls>"]},
            ['blocking']
);
""" % (PROXY_HOST, PROXY_PORT, PROXY_USER, PROXY_PASS)

def get_chromedriver(use_proxy=False):
    user_agents = [
        # Google Chrome
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/605.1.15',
        'Mozilla/5.0 (Linux; Android 10; SM-G975F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Mobile Safari/537.36',
        
        # Mozilla Firefox
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:90.0) Gecko/20100101 Firefox/90.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7; rv:90.0) Gecko/20100101 Firefox/90.0',
        'Mozilla/5.0 (Linux; Android 10; SM-G973F; rv:90.0) Gecko/90.0 Firefox/90.0',

        # Microsoft Edge
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36 Edg/90.0.818.66',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/90.0.818.66 Safari/605.1.15 Edg/90.0.818.66',
        
        # Safari
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
        
        # Opera
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36 OPR/76.0.4017.123',
        'Mozilla/5.0 (Linux; Android 10; SM-A505F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Mobile OPR/76.0.4017.123',
        
        # Internet Explorer
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; Trident/7.0; AS; rv:11.0) like Gecko',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko',
        
        # Otros
        'Mozilla/5.0 (Linux; Android 9; Pixel 3 XL Build/PQ3A.190705.003) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.111 Mobile Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36',
    ]
    
    path = os.path.dirname(os.path.abspath(__file__))
    
    chrome_options = Options()
    
    # Deshabilitar la detección de automatización
    chrome_options.add_experimental_option('excludeSwitches', ['enable-automation'])
    chrome_options.add_experimental_option('useAutomationExtension', False)

    # Cambiar el user-agent para parecer un navegador real
    user_agent = random.choice(user_agents)
    chrome_options.add_argument(f'--user-agent={user_agent}')

    # Ocultar el hecho de que estamos utilizando un WebDriver
    chrome_options.add_argument('--disable-blink-features=AutomationControlled')

    # Maximizar la ventana para simular un navegador normal
    chrome_options.add_argument('--start-maximized')

    # Desactivar WebRTC para evitar la fuga de IP real
    chrome_options.add_argument('--disable-webrtc')

    # Activar el modo de incógnito
    # chrome_options.add_argument('--incognito')

    # Habilitar el uso de WebGL y aceleración de hardware
    chrome_options.add_argument('--enable-webgl')
    chrome_options.add_argument('--enable-accelerated-2d-canvas')

    # Deshabilitar el rasterizador de software para mejorar el rendimiento
    chrome_options.add_argument('--disable-software-rasterizer')

    # Ocultar las notificaciones del navegador
    chrome_options.add_argument('--disable-notifications')
    
    chrome_options.add_argument("--disable-dev-shm-usage")  # Desactiva el uso del espacio compartido para evitar problemas de inicio

    # Habilitar soporte para WebGL y GPU pero evitar usar GPU en headless mode
    chrome_options.add_argument('--disable-gpu')  # Necesario para algunos sistemas
    chrome_options.add_argument('--no-sandbox')   # Solucionar posibles errores de sandbox
    chrome_options.add_argument('--disable-dev-shm-usage')  # Evitar el uso de memoria compartida

    # Simular resolución de pantalla estándar para mejorar la compatibilidad
    chrome_options.add_argument('--window-size=1920x1080')

    # Añadir un pequeño delay al cargarse las páginas para imitar un navegador humano
    chrome_options.add_argument('--enable-smooth-scrolling')

    # Evitar la carga de imágenes para hacer más rápidas las solicitudes
    chrome_options.add_argument('--blink-settings=imagesEnabled=false')

    # Deshabilitar características de Chrome innecesarias para reducir detecciones
    # chrome_options.add_argument('--disable-extensions')
    chrome_options.add_argument('--disable-popup-blocking')
    chrome_options.add_argument('--disable-infobars')
    chrome_options.add_argument('--headless')
    if use_proxy:
        pluginfile = 'proxy_auth_plugin.zip'
        
        if not os.path.exists(pluginfile):
            print('Haciendo plugin de proxy')
            with zipfile.ZipFile(pluginfile, 'w') as zp:
                zp.writestr("manifest.json", manifest_json)
                zp.writestr("background.js", background_js)
        else:
            print('Plugin de proxy ya existe')
            
        chrome_options.add_extension(pluginfile)
        
    driver = 0
        
    if(is_debug):
        driver = webdriver.Chrome(options=chrome_options)
    else:
        service = Service('/usr/bin/chromedriver')
        driver = webdriver.Chrome(service=service, options=chrome_options)
        
    return driver

def desbloquear(all, procesed):
    resoult = [elem for elem in all if elem not in procesed]
    data_send_unlock = {'numbers': resoult}
    url_unlock = f'{HOST_BACK}/unlock_numbers.php'
    response_post = requests.post(url_unlock, json=data_send_unlock)
    print(data_send_unlock)

def main():
    try:
        last_audio_folder = ''
        
        processed_numbers = list()
        
        all_numbers = list()
        
        # Obtener numeros
        urlGetNumber = f'{HOST_BACK}/get_next_number.php'
        
        urlPostNumber = f'{HOST_BACK}/set_number.php'
        
        responseNumbers = requests.get(urlGetNumber)
        
        all_numbers = responseNumbers.json()

        driver = get_chromedriver(use_proxy=True)

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
            
            # time.sleep(5)
            
            for objNumero in responseNumbers.json():
                numero_a_consultar = objNumero['number']
                print(f"Número a consultar: {numero_a_consultar}")
                # Genera un nombre de carpeta único basado en numero_a_consultar
                audio_folder = os.path.join(os.getcwd(), f"audio_{numero_a_consultar}")
                last_audio_folder = audio_folder
                os.makedirs(audio_folder, exist_ok=True)

                # Espera a que el input sea clickeable y lo guarda en una variable
                input_element = driver.find_element(By.CSS_SELECTOR, ".v-input__control .v-field__input")

                # Verifica si el elemento está habilitado antes de intentar hacer clic
                if input_element.is_enabled():
                    # Hace clic y escribe el texto
                    input_element.click()
                    if input_element.get_attribute("value"):
                            input_element.send_keys(Keys.BACKSPACE * len(input_element.get_attribute("value")))
                    for char in numero_a_consultar:
                        input_element.send_keys(char)
                        time.sleep(random.uniform(0.1, 0.3))
                        
                    # input_element.send_keys(numero_a_consultar)
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
                
                text_search_captcha = ''
                
                if is_debug:
                    text_search_captcha = 'el desafío de recaptcha caduca dentro de dos minutos'
                else:
                    text_search_captcha = 'recaptcha challenge expires in two minutes'

                for frame in frames:
                    title = frame.get_attribute("title")
                    print(f"Frame title: {title}")  # Para depurar
                    if title and re.search('reCAPTCHA', title):
                        recaptcha_control_frame = frame
                    if title and re.search(text_search_captcha, title):
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

                        path_to_mp3 = os.path.normpath(os.path.join(audio_folder, "sample.mp3"))
                        path_to_wav = os.path.normpath(os.path.join(audio_folder, "sample.wav"))

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

                    finally:
                        # Elimina el directorio y su contenido
                        shutil.rmtree(audio_folder)
                        print(f"[INFO] Directory {audio_folder} has been removed.")

                # Vuelve a la página inicial
                driver.switch_to.default_content()
                
                # Esperar 2 segundos
                time.sleep(2)
                
                # Clickea en el botón de aceptar
                WebDriverWait(driver, 20).until(
                    EC.element_to_be_clickable((By.CSS_SELECTOR, ".v-container .v-row .v-col button"))
                ).click()

                # Obtén el texto del párrafo
                text_operator = WebDriverWait(driver, 20).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, ".v-container .v-row .v-col-lg-8 .v-card > :nth-of-type(4)"))
                )
                
                data_send = {
                    'id': objNumero['id'],
                    'operator': text_operator.text
                }
                
                processed_numbers.append(objNumero)
                
                response_post = requests.post(urlPostNumber, json=data_send)
            
                # Devuelve el resultado como respuesta
                # return jsonify({"message": "Script ejecutado con éxito", "operator": text_operator.text})
                
                print(data_send)

        except Exception as e:
            print(f"Error: {e}")
            print(traceback.format_exc())
            desbloquear(all_numbers, processed_numbers)
            if(os.path.exists(last_audio_folder)):
                shutil.rmtree(last_audio_folder)
        finally:
            # Cierra el navegador solo si sigue abierto
            try:
                driver.quit()
            except Exception as e:
                print(f"Error closing the browser: {e}")
                desbloquear(all_numbers, processed_numbers)
                if(os.path.exists(last_audio_folder)):
                    shutil.rmtree(last_audio_folder)
    except Exception as e:
        traceback.print_exc()
        desbloquear(all_numbers, processed_numbers)
        if(os.path.exists(last_audio_folder)):
            shutil.rmtree(last_audio_folder)
    
if __name__ == '__main__':
    main()