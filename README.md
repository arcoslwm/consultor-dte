## Consultor de Documento Tributario electronico sobre PHP Slim Framework v3.5
---

usa skeleton: (https://github.com/ricardoper/slim3-skeleton)


## Requisitos
---
- PHP 5 >= 5.5
- extension SOAP php : llamadas a WS soap
- extension cURL php : llamada API recaptcha
- extension OpenSSL PHP : encriptacion argumentos descarga

## Configuracion
---
- apuntar el virtual host a la carpeta ```public/``` de la aplicación
- aprovisionar un archivo ```.env``` con las variables de entorno correspondientes definidas en ```.env.example```
- las carpetas ```log/``` y ```storage/``` necesitan permisos de escritura para el usuario que ejecuta el servidor web.

## Uso
---

#### Formulario de busqueda:
---
http(s)://virtualhost.dom/
