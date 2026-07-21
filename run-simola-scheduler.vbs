Option Explicit

Dim shell
Dim batFile
Dim exitCode

batFile = "C:\xampp\htdocs\simola\run-simola-scheduler.bat"

Set shell = CreateObject("WScript.Shell")

shell.CurrentDirectory = "C:\xampp\htdocs\simola"

exitCode = shell.Run(Chr(34) & batFile & Chr(34), 0, True)

WScript.Quit exitCode