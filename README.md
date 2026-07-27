MapleBit
============
Please note that this is not mine, i just grabbed MapleBit v1.20 and added bcrypt support to ensure there is a stable version where character pictures display properly, and registration/account login works.

SoloMapling local setup
============
This fork includes a Windows development setup for the Cosmic-compatible
SoloMapling server in `C:\MapleServer`.

```powershell
.\Install-MapleWeb.ps1
.\Start-MapleWeb.ps1
```

The site listens on `http://127.0.0.1:8080/` by default. Database credentials
are read at runtime from `C:\MapleServer\config.yaml`; they are not stored in
this repository. Use `.\Start-MapleWeb.ps1 -Background` to start it in the
background and `.\Stop-MapleWeb.ps1` to stop that background process.

Local development mode bypasses reCAPTCHA only for loopback requests. Configure
real reCAPTCHA keys and disable `MAPLE_LOCAL_DEV` before publishing the site.

Installation
============
To install the CMS, you need to upload the entire .zip to your web server. Then, extract the .zip, and open your browser. Navigate to the location in which you extracted the .zip. You'll be presented with a web based installer. Next, enter in your database connection details, and configure your website. Once you've done this, the installer will lock itself and redirect you to your website. 

**Troubleshooting**
- If character pictures are not showing up, please ensure that you extract GD v148.7z files in assets/img/GD
- if the backgrounds dont change, you must edit css files to change the background for each template

Features
============
Some features of MapleBit include:

 - **Automatic and Flexible Installer.** All you have to do is have your database connection information handy, and put them in the web based installer. You can also use a custom database prefix to have multiple installations of MapleBit in one database.
 - **Customizable.** You can change the site's theme, using BootSwatch themes. Then, you can add your own banner and background. This allows you to use MapleBit and display the uniqueness of your server and website.
 - **User Profiles.** Your users can have a richer interaction with your website once they signup for a display name, which will allow them to comment on news, events, gmblogs, and have profile pages.
 - **Modern.** MapleBit uses modern web technology such as HTML5, CSS3, and MySQLi. MapleBit is also responsive, so all devices can use the website.
 - **Beautiful.** Rankings MapleBit uses a powerful GD library that produces accurate and beautiful character displays.
 - **Comments.** MapleBit features a commenting system which allows users to post comments with Gravatars and rich formatting.
