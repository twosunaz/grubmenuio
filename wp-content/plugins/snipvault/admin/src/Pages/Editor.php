<?php
namespace SnipVault\Pages;
use SnipVault\Utility\Scripts;
use SnipVault\Ajax\CLIExecutor;

// Prevent direct access to this file
defined("ABSPATH") || exit();

/**
 * Class snipvault
 *
 * Main class for initialising the snipvault app.
 */
class Editor
{
  private static $options = null;
  /**
   * snipvault constructor.
   *
   * Initialises the main app.
   */
  public function __construct()
  {
    add_action("admin_menu", [$this, "admin_settings_page"]);
    new CLIExecutor();
  }

  /**
   * Adds settings page.
   *
   * Calls add_menu_page to add new page .
   */
  public static function admin_settings_page()
  {
    $icon =
      "data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMDAiIGhlaWdodD0iMTAwMCI+CjxwYXRoIGQ9Ik0wIDAgQzE0LjMwNDcyNTM0IDEyLjA4MjYzMjA4IDIzLjc1MzIwNDE1IDI4LjM1NTE2NjE2IDI1LjY4NzUgNDcuMTI1IEMyNi44MjMwMTc3IDY2LjI4OTM1MTM3IDIyLjc3NDQwNjI3IDgzLjg1MDg3NzA2IDEwLjY4NzUgOTkuMTI1IEMxMC4wNDE2Nzk2OSA5OS45NTkwMjM0NCA5LjM5NTg1OTM3IDEwMC43OTMwNDY4OCA4LjczMDQ2ODc1IDEwMS42NTIzNDM3NSBDLTIuMjY1MTgwODggMTE0Ljg5MjAwMzUxIC0xOC4yMTAwMTc2OCAxMjIuNTE0MDM3MjUgLTM0Ljk4MjY2NjAyIDEyNS4zOTg0Mzc1IEMtMzYuMDY1OTY3MjYgMTI1LjU5NjAxNjU0IC0zNy4xNDkyNjg1MSAxMjUuNzkzNTk1NTggLTM4LjI2NTM5NzA3IDEyNS45OTcxNjE4NyBDLTQxLjUxODQxMTU5IDEyNi41ODUyMDMyMyAtNDQuNzczNDM2MDcgMTI3LjE1ODcyODg1IC00OC4wMzAwODUwOSAxMjcuNzI2MTIgQy01MC40NzYwOTA4OCAxMjguMTUzNTg5NTEgLTUyLjkyMTIwODYgMTI4LjU4NTg4NjEgLTU1LjM2NjE5MTg2IDEyOS4wMTkxNjUwNCBDLTY4LjkzMjM5NTU4IDEzMS40MjI0ODg2NiAtODIuNTA1NTY1NTggMTMzLjc4NTA1OTE3IC05Ni4wODAxMzIyNSAxMzYuMTQwNjE3MzcgQy0xMDIuMDM2MTI5MjUgMTM3LjE3NDc0MjU3IC0xMDcuOTkxNjIwOTYgMTM4LjIxMTc2OTU2IC0xMTMuOTQ3MTU4ODEgMTM5LjI0ODUzNTE2IEMtMTI2LjQ5MTI0OTA1IDE0MS40MzE4MzM5MSAtMTM5LjAzNTgxODIgMTQzLjYxMjM3MDQgLTE1MS41ODA1MDUzNyAxNDUuNzkyMjM2MzMgQy0xNzAuNDQ3NTI0NzUgMTQ5LjA3MDcyNzU1IC0xODkuMzE0MTk2NjUgMTUyLjM1MTIxNTE4IC0yMDguMTgwMzU1MTMgMTU1LjYzNDY1NjkxIEMtMjE1LjIyMTcwNjQ1IDE1Ni44NjAwMTQ2OCAtMjIyLjI2MzMyNDczIDE1OC4wODM4MjMxOCAtMjI5LjMwNTE0ODQyIDE1OS4zMDY0NjMyNCBDLTI2Mi4zNDAzNTMzNSAxNjUuMDQwNzY2ODcgLTI2Mi4zNDAzNTMzNSAxNjUuMDQwNzY2ODcgLTI5NS4zMTI1IDE3MS4xMjUgQy0yOTMuODE3MTkwMDIgMTcxLjY4NDcxNDk3IC0yOTMuODE3MTkwMDIgMTcxLjY4NDcxNDk3IC0yOTIuMjkxNjcxNzUgMTcyLjI1NTczNzMgQy0yNzAuMjI1MTQzMDggMTgwLjUxNjM5NjMyIC0yNDguMTcxMTU1MTQgMTg4LjgxMDEzMDcgLTIyNi4xMjUgMTk3LjEyNSBDLTIyNS40ODA4NTI3IDE5Ny4zNjc5NDI5NiAtMjI0LjgzNjcwNTQgMTk3LjYxMDg4NTkzIC0yMjQuMTczMDM4NDggMTk3Ljg2MTE5MDggQy0yMTcuNTMwMTMyMDggMjAwLjM2NjYzMTggLTIxMC44ODczNDI1NSAyMDIuODcyMzgyNTcgLTIwNC4yNDQ2MjIyMyAyMDUuMzc4MzE2ODggQy0xOTEuNTU5ODAwMTYgMjEwLjE2MzU5ODU5IC0xNzguODc0MjY3MjUgMjE0Ljk0Njk5NDYyIC0xNjYuMTg4NjI1MzQgMjE5LjczMDEwMjU0IEMtMTY0LjMwNzM1NTMxIDIyMC40Mzk0Mzk4MiAtMTYyLjQyNjA5MTkxIDIyMS4xNDg3OTQ2NiAtMTYwLjU0NDgzMDMyIDIyMS44NTgxNTQzIEMtMTQyLjE1Njk0MDQyIDIyOC43OTE1NzEwNSAtMTIzLjc2Nzk4NjgzIDIzNS43MjIxNjE3NCAtMTA1LjM3ODM3NDEgMjQyLjY1MTAwNzY1IEMtMTAwLjI5MTA2OTM4IDI0NC41Njc4MjY2OSAtOTUuMjAzODYzMDcgMjQ2LjQ4NDkwNjc1IC05MC4xMTY2OTkyMiAyNDguNDAyMDk5NjEgQy04Ny4yMzE0NTA5NSAyNDkuNDg5NDM1NTMgLTg0LjM0NjE5NjE0IDI1MC41NzY3NTQxIC04MS40NjA5Mzc1IDI1MS42NjQwNjI1IEMtODAuMzkzMzE5OTUgMjUyLjA2NjM5ODIzIC04MC4zOTMzMTk5NSAyNTIuMDY2Mzk4MjMgLTc5LjMwNDEzNDM3IDI1Mi40NzY4NjE5NSBDLTcxLjQ3ODQxNjMzIDI1NS40MjU5MzUwOCAtNjMuNjUyMzEyODUgMjU4LjM3Mzk3ODk5IC01NS44MjUxOTUzMSAyNjEuMzE5MzM1OTQgQy01NS4xMzExOTM0NiAyNjEuNTgwNDkwMzcgLTU0LjQzNzE5MTYgMjYxLjg0MTY0NDggLTUzLjcyMjE1OTM5IDI2Mi4xMTA3MTMwMSBDLTUyLjMzMDI5NTYyIDI2Mi42MzQ0Njk1IC01MC45Mzg0MzE4IDI2My4xNTgyMjU4NSAtNDkuNTQ2NTY3OTIgMjYzLjY4MTk4MjA0IEMtMzYuMjMxMTE4OTggMjY4LjY5MjY2NTM4IC0yMi45MTkyMzI4OCAyNzMuNzEyNjQzMDcgLTkuNjExODE2NDEgMjc4Ljc0NDYyODkxIEMtNS42OTA5Njk2NyAyODAuMjI3MDk1ODMgLTEuNzY4OTg1ODcgMjgxLjcwNjQ0MzQxIDIuMTU0Nzg1MTYgMjgzLjE4MTE1MjM0IEM1LjkyNDc2NzI2IDI4NC41OTgyNTY3NSA5LjY5MTQ5MDY5IDI4Ni4wMjM1ODgzOSAxMy40NTQ4OTUwMiAyODcuNDU4MDY4ODUgQzE1LjI2NjIwNzUyIDI4OC4xNDYxNzA2OCAxNy4wODEwMDAyMyAyODguODI1MDkxMjcgMTguODk1OTk2MDkgMjg5LjUwMzQxNzk3IEMzMC4yMTYwNzU4MiAyOTMuODQ2MDU3MjUgMzkuMDM4NDQ1MyAyOTkuNjQ2NjUwMzMgNDcuNjg3NSAzMDguMTI1IEM0OC4zODg3NSAzMDguNzkwMTU2MjUgNDkuMDkgMzA5LjQ1NTMxMjUgNDkuODEyNSAzMTAuMTQwNjI1IEM2My4yNjgzODMwNSAzMjMuOTExODgwMzEgNjcuMTkzNTgzNzYgMzQxLjcxMzI5NTQgNjcuMDM1MTU2MjUgMzYwLjMzOTg0Mzc1IEM2Ni41NjcyMzIwNiAzODAuMDY2NTQyNTMgNTguNzM3MTAwNDEgMzk1LjU3NDk0MDkzIDQ0LjY4NzUgNDA5LjEyNSBDNDMuNzM4MTA1NDcgNDEwLjEyMDgwMDc4IDQzLjczODEwNTQ3IDQxMC4xMjA4MDA3OCA0Mi43Njk1MzEyNSA0MTEuMTM2NzE4NzUgQzMwLjU5MjAxNzE5IDQyMy4wOTkxNzczNyAxMy40NzIxMDg4OCA0MjcuMjk1NTU3ODIgLTMgNDI3LjYyNSBDLTQuNDMyNzkyOTcgNDI3LjY3MTQwNjI1IC00LjQzMjc5Mjk3IDQyNy42NzE0MDYyNSAtNS44OTQ1MzEyNSA0MjcuNzE4NzUgQy0yMS4wNDg1ODc5MiA0MjcuNzE4NzUgLTM1LjYxMjAxMTIgNDIxLjA4MjQ4MjQ0IC00OS41NjI1IDQxNS43NSBDLTUxLjI2ODM5NTU5IDQxNS4xMDM1MzE3MyAtNTIuOTc0NTQxODMgNDE0LjQ1NzcyNDU4IC01NC42ODA5MDgyIDQxMy44MTI1IEMtNTguNDY4MjM1NDQgNDEyLjM3OTU0MzczIC02Mi4yNTQyNjkwOCA0MTAuOTQzMjMzNDkgLTY2LjAzOTU1MDc4IDQwOS41MDQ4ODI4MSBDLTcyLjgwMTk2ODYyIDQwNi45MzcwMTg3NCAtNzkuNTcwNTUyNTEgNDA0LjM4NTYxNzQ2IC04Ni4zMzk4NDM3NSA0MDEuODM1OTM3NSBDLTg4LjE5ODcwNDQ4IDQwMS4xMzU3MzMxNyAtOTAuMDU3NTY1MiA0MDAuNDM1NTI4ODQgLTkxLjkxNjQyNTcgMzk5LjczNTMyMzkxIEMtOTMuMTgwNzkxMDggMzk5LjI1OTA2MDExIC05NC40NDUxNjEwOCAzOTguNzgyODA4NiAtOTUuNzA5NTM1NiAzOTguMzA2NTY5MSBDLTEwMi45MjU3NjI3IDM5NS41ODgzMTgzMyAtMTEwLjE0MDYyMjE0IDM5Mi44NjY0NDM1NCAtMTE3LjM1NTQ2ODc1IDM5MC4xNDQ1MzEyNSBDLTEyMC4wMzE3MzI5MSAzODkuMTM0OTE0MTQgLTEyMi43MDgwMDQzMSAzODguMTI1MzE2MjQgLTEyNS4zODQyNzczNCAzODcuMTE1NzIyNjYgQy0xMjYuMDQ0NDM5MTEgMzg2Ljg2NjY4MjE0IC0xMjYuNzA0NjAwODcgMzg2LjYxNzY0MTYyIC0xMjcuMzg0NzY3NTMgMzg2LjM2MTA1NDQyIEMtMTM4LjczNDgzMTA0IDM4Mi4wNzk0MTY2OSAtMTUwLjA4NTYxNTA2IDM3Ny43OTk2ODk0MSAtMTYxLjQzNjM3NDY2IDM3My41MTk4OTc0NiBDLTE2My4zMTc2NDQ2OSAzNzIuODEwNTYwMTggLTE2NS4xOTg5MDgwOSAzNzIuMTAxMjA1MzQgLTE2Ny4wODAxNjk2OCAzNzEuMzkxODQ1NyBDLTE4Ny42NTY0OTAyNiAzNjMuNjMzMjQ5ODggLTIwOC4yMzQyNTk1NiAzNTUuODc4NTAyMTIgLTIyOC44MTI1IDM0OC4xMjUgQy0yNTguMTQ4Nzk2NTkgMzM3LjA3MTYyMzU3IC0yODcuNDgzMTA2MzIgMzI2LjAxMjk4NjMgLTMxNi44MTY0MDYyNSAzMTQuOTUxNjYwMTYgQy0zMjQuMzg3OTU4NjcgMzEyLjA5NjUxNDgxIC0zMzEuOTU5NTkxNzYgMzA5LjI0MTU4MzQ2IC0zMzkuNTMxMjUgMzA2LjM4NjcxODc1IEMtMzQwLjE2MTA3Nzg4IDMwNi4xNDkyNDQyMyAtMzQwLjc5MDkwNTc2IDMwNS45MTE3Njk3MSAtMzQxLjQzOTgxOTM0IDMwNS42NjcwOTkgQy0zNDUuODk3Mzc3MDggMzAzLjk4NjM5Mjc3IC0zNTAuMzU0OTM4MTIgMzAyLjMwNTY5NTI2IC0zNTQuODEyNSAzMDAuNjI1IEMtMzU5LjkwNjI1MjMxIDI5OC43MDQ0MzMyIC0zNjUuMDAwMDAyODYgMjk2Ljc4Mzg2MTc1IC0zNzAuMDkzNzUgMjk0Ljg2MzI4MTI1IEMtMzcwLjcyMzczOTAxIDI5NC42MjU3NDYzMSAtMzcxLjM1MzcyODAzIDI5NC4zODgyMTEzNiAtMzcyLjAwMjgwNzYyIDI5NC4xNDM0NzgzOSBDLTM4MC4xODM0OTEgMjkxLjA1ODk3NjY2IC0zODguMzY0MTEzNTcgMjg3Ljk3NDMxMzczIC0zOTYuNTQ0Njc3NzMgMjg0Ljg4OTQ5NTg1IEMtNDAyLjgwMDU5ODk0IDI4Mi41MzA0NDUzOSAtNDA5LjA1NjUzOTM3IDI4MC4xNzE0NDU5MiAtNDE1LjMxMjUgMjc3LjgxMjUgQy00MTYuMjQ1ODIxNTMgMjc3LjQ2MDU3MDgzIC00MTYuMjQ1ODIxNTMgMjc3LjQ2MDU3MDgzIC00MTcuMTk3OTk4MDUgMjc3LjEwMTUzMTk4IEMtNDM0LjAyOTE4MzE1IDI3MC43NTUwMTI5MyAtNDUwLjg2MTAwMDk0IDI2NC40MTAxNzQ5NSAtNDY3LjY5MzgzMjQgMjU4LjA2ODAyMzY4IEMtNDY5LjE0OTMyMjczIDI1Ny41MTk2MzM1NiAtNDcwLjYwNDgxMjg0IDI1Ni45NzEyNDI4NiAtNDcyLjA2MDMwMjczIDI1Ni40MjI4NTE1NiBDLTQ3Mi43ODI2MzA2MSAyNTYuMTUwNjk3NjEgLTQ3My41MDQ5NTg0OCAyNTUuODc4NTQzNjYgLTQ3NC4yNDkxNzUwNyAyNTUuNTk4MTQyNjIgQy00NzkuMzI5MTQyNDkgMjUzLjY4NDA4MDM5IC00ODQuNDA4ODc1ODIgMjUxLjc2OTM5Nzc4IC00ODkuNDg4NTI1MzkgMjQ5Ljg1NDQ5MjE5IEMtNDkyLjM2ODYzMjg3IDI0OC43Njg4Mjg1MyAtNDk1LjI0ODc2MTg0IDI0Ny42ODMyMjE5MiAtNDk4LjEyODkwNjI1IDI0Ni41OTc2NTYyNSBDLTQ5OC44Mzk1NTc5NyAyNDYuMzI5Nzk2ODcgLTQ5OS41NTAyMDk2OSAyNDYuMDYxOTM3NDggLTUwMC4yODIzOTYzMiAyNDUuNzg1OTYxMTUgQy01MDkuNTk0MzM3NyAyNDIuMjc2Mzc3NTQgLTUxOC45MDg1NjAzMyAyMzguNzcyOTI4MDQgLTUyOC4yMjUwNjcxNCAyMzUuMjc1NDgyMTggQy01MzMuMDg1OTc5OTQgMjMzLjQ1MDYzMDIxIC01MzcuOTQ2MTA2NjQgMjMxLjYyMzY4ODgyIC01NDIuODA2MDQzNjIgMjI5Ljc5NjIzOTg1IEMtNTQ2LjA0NzYxNzI4IDIyOC41Nzc1MDYyMyAtNTQ5LjI4OTYwMTA4IDIyNy4zNTk4OTM2NCAtNTUyLjUzMjcxNDg0IDIyNi4xNDUyNjM2NyBDLTU2NC42MjgzNzIyOCAyMjEuNjEzMjE3ODMgLTU3Ni43MDM0MTAyMyAyMTcuMDQxNDk0NiAtNTg4LjY3NTc4MTI1IDIxMi4xOTE0MDYyNSBDLTU4OS41NzEyMzE1NCAyMTEuODMxNTUxMzYgLTU5MC40NjY2ODE4MiAyMTEuNDcxNjk2NDcgLTU5MS4zODkyNjY5NyAyMTEuMTAwOTM2ODkgQy01OTcuNjc4NTUzOCAyMDguNTIyNjU0MzYgLTYwMy4xMjczNTMyNiAyMDUuNTcwODAxMjQgLTYwOC4zMTI1IDIwMS4xMjUgQy02MDkuMTMzNjMyODEgMjAwLjQ4NDMzNTk0IC02MDkuOTU0NzY1NjIgMTk5Ljg0MzY3MTg4IC02MTAuODAwNzgxMjUgMTk5LjE4MzU5Mzc1IEMtNjIzLjM1Njk1OTI5IDE4OC44Mzc2MDc5IC02MzEuNzUxMjU0MzQgMTczLjE0MjU3NjA3IC02MzQuMzEyNSAxNTcuMTI1IEMtNjM2LjEwODAwOTU1IDEzNS43NTkwMzAyNiAtNjMyLjQ0MjcyMjQzIDExNy42MDg5MTc5NyAtNjE5LjA2MjUgMTAwLjM3NSBDLTYwMS4wOTQ4MzQ5NyA3OS41Mjg4NzI2MSAtNTc3LjU5OTI1NTE0IDc1LjY3NDEzNTk2IC01NTEuOTE2NzQ4MDUgNzEuMjYzMTgzNTkgQy01NDkuNzkzMjUyOCA3MC44OTM5MzU2MSAtNTQ3LjY2OTg0ODc5IDcwLjUyNDE2MjU5IC01NDUuNTQ2NTI3ODYgNzAuMTUzOTEzNSBDLTUzOS43NzExOTk4NiA2OS4xNDgzNjYwNyAtNTMzLjk5NDYzMzY1IDY4LjE1MDEwNDE4IC01MjguMjE3ODI3NTYgNjcuMTUzMDg4NTcgQy01MjIuMDE4Mjg1MDQgNjYuMDgxNzQ3MjggLTUxNS44MTk5NTAzMyA2NS4wMDM0NjA5NyAtNTA5LjYyMTQ3NTIyIDYzLjkyNTk2NDM2IEMtNDk4Ljk3MTE1MzQ3IDYyLjA3NTUxMzM2IC00ODguMzIwMTI3NzkgNjAuMjI5MTc1NTIgLTQ3Ny42Njg1NzkxIDU4LjM4NTgwMzIyIEMtNDYxLjYyMjk0MDIyIDU1LjYwODg2NjgyIC00NDUuNTc4NTk5MzUgNTIuODI0NDgyNjcgLTQyOS41MzQ1NDM3OCA1MC4wMzg0MTQ5MyBDLTQyMy4xNzMxODk4NCA0OC45MzM3ODQ4IC00MTYuODExNzk5NjQgNDcuODI5MzYzNDYgLTQxMC40NTA0MDg5NCA0Ni43MjQ5NDUwNyBDLTQwOS43NDg3NTk2NiA0Ni42MDMxMjcwNSAtNDA5LjA0NzExMDM4IDQ2LjQ4MTMwOTA0IC00MDguMzI0MTk5MDEgNDYuMzU1Nzk5NTcgQy0zOTcuNjI2OTc2NDYgNDQuNDk4NjEwOTcgLTM4Ni45Mjk1MzI1MiA0Mi42NDI3MDEzNyAtMzc2LjIzMTk2NDExIDQwLjc4NzUwNjEgQy0zNDAuNTQ4ODQzODYgMzQuNTk5MDg2MDMgLTMwNC44NjgzNjA2OCAyOC4zOTU3NjUzMyAtMjY5LjE5MTQwNjI1IDIyLjE3MTg3NSBDLTI2OC4wODM5MzA5MSAyMS45Nzg2ODM4NCAtMjY2Ljk3NjQ1NTU3IDIxLjc4NTQ5MjY4IC0yNjUuODM1NDIwMzcgMjEuNTg2NDQ3MjQgQy0yNTIuOTU3NDcxNzMgMTkuMzM5OTU1MiAtMjQwLjA3OTY0ODM1IDE3LjA5Mjc0NTc2IC0yMjcuMjAxODk4NCAxNC44NDUxMTUwNyBDLTIxNy4zMjM4MzAxOSAxMy4xMjEwNDE5MSAtMjA3LjQ0NTcwNDIgMTEuMzk3MzAwMTUgLTE5Ny41Njc1MjYyMiA5LjY3Mzg1NjAyIEMtMTk0LjczODIzNDkzIDkuMTgwMTc3MzkgLTE5MS45MDg5Njk5NCA4LjY4NjM0ODI0IC0xODkuMDc5NzA5MDUgOC4xOTI0OTUzNSBDLTE4MC4wNzE4ODIxNiA2LjYyMDQwOTIyIC0xNzEuMDYzNTc1NDkgNS4wNTExNzA3OSAtMTYyLjA1NDE1MzQ0IDMuNDg4MjUwNzMgQy0xNTguNjA3MDE3MDIgMi44ODk5MDg5MiAtMTU1LjE1OTkzNTE4IDIuMjkxMjUzNjIgLTE1MS43MTI4OTA2MiAxLjY5MjM4MjgxIEMtMTUwLjg3MDUwNjM0IDEuNTQ2MDcyNzIgLTE1MC4wMjgxMjIwNSAxLjM5OTc2MjY0IC0xNDkuMTYwMjEwOTcgMS4yNDkwMTg5MSBDLTEzNy44ODkxMjQ3MyAtMC43MTAxNzQ0IC0xMjYuNjI2MTQ0NzYgLTIuNzEwNzE1ODUgLTExNS4zNjg1ODc0OSAtNC43NDYxNzU3NyBDLTEwOS4zMjA4MjUxMiAtNS44MzkzNzE2MiAtMTAzLjI3MDc3ODIxIC02LjkxOTY2NjE1IC05Ny4yMjAxNDI2IC03Ljk5NjgzNDQgQy05My4xODY2OTY1IC04LjcxNTg3OTIgLTg5LjE1NDgwNjg3IC05LjQ0MTg1ODMgLTg1LjEyNTk5OTQ1IC0xMC4xODY0ODkxMSBDLTU0LjcwNTk1NDE4IC0xNS44MDI5MjMxMSAtMjYuMjc2MTM4MDQgLTIwLjEwNzY1NDQxIDAgMCBaICIgZmlsbD0iIzAwMDAwMCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNzkwLjMxMjUsMTUwLjg3NSkiLz4KPHBhdGggZD0iTTAgMCBDMC45OTcwODk4NCAtMC4wMTQxNzk2OSAxLjk5NDE3OTY5IC0wLjAyODM1OTM3IDMuMDIxNDg0MzggLTAuMDQyOTY4NzUgQzE0LjE5MjIyNTYzIDAuMDMwMjgyMDEgMjQuMjgwNjgzMzUgMy4yNDEzNjU4IDM0LjYyMTA5Mzc1IDcuMjM0Mzc1IEMzNi4xMDg5NDEyNyA3Ljc5OTE1MDU0IDM2LjEwODk0MTI3IDcuNzk5MTUwNTQgMzcuNjI2ODQ2MzEgOC4zNzUzMzU2OSBDNDAuODk5NDQxMTIgOS42MTkwNzc4NiA0NC4xNjgzODUyMyAxMC44NzIxNDI2IDQ3LjQzNzUgMTIuMTI1IEM0OS43ODg3NzcxNCAxMy4wMjAxMTg0MiA1Mi4xNDAzNDY4MyAxMy45MTQ0Njg3MiA1NC40OTIxODc1IDE0LjgwODEwNTQ3IEM1OC4xOTc3ODE1NCAxNi4yMTY1MDg4IDYxLjkwMzIxOTQzIDE3LjYyNTMwNjAzIDY1LjYwNzM5MTM2IDE5LjAzNzQ0NTA3IEM3Ny45NzMxNjkzMyAyMy43NTE1NTQ4OSA5MC4zNTU5MzQwNSAyOC40MjAyNjQzNCAxMDIuNzQwNzUzMTcgMzMuMDg0MTA2NDUgQzEwNi41NDIyODIwNiAzNC41MTU3NTI1NSAxMTAuMzQzMzQwNjcgMzUuOTQ4NjQ0MjcgMTE0LjE0NDI4NzExIDM3LjM4MTgzNTk0IEMxMTQuNzgyNzc2MTkgMzcuNjIyNTgzNDcgMTE1LjQyMTI2NTI4IDM3Ljg2MzMzMDk5IDExNi4wNzkxMDI1MiAzOC4xMTEzNzM5IEMxMTcuMzc4MTQzNzggMzguNjAxMTg5OTYgMTE4LjY3NzE4NDU3IDM5LjA5MTAwNzI3IDExOS45NzYyMjQ5IDM5LjU4MDgyNTgxIEMxMzguOTQzNjYyMjcgNDYuNzMyMzgxMjIgMTU3LjkxMzAwODQzIDUzLjg3ODg3MzQ2IDE3Ni44ODIwODAwOCA2MS4wMjYwOTI1MyBDMTk3LjI1NTYxOTkgNjguNzAyNTA5MzMgMjE3LjYyODM4NTE4IDc2LjM4MDk3NjA1IDIzOCA4NC4wNjI1IEMyMzguNjIzMzkyNjQgODQuMjk3NTYxMyAyMzkuMjQ2Nzg1MjggODQuNTMyNjIyNiAyMzkuODg5MDY4NiA4NC43NzQ4MDY5OCBDMjUwLjUwMjYwMzMyIDg4Ljc3NjgzNTc0IDI2MS4xMTYwNDk4NyA5Mi43NzkwOTgyOCAyNzEuNzI5NDkyMTkgOTYuNzgxMzcyMDcgQzI4MC40ODYyOTM0OCAxMDAuMDgzNTA2NjEgMjg5LjI0MzEyMzkxIDEwMy4zODU1NjM4NCAyOTggMTA2LjY4NzUgQzI5OC42MjI3NDgxMSAxMDYuOTIyMzE5NiAyOTkuMjQ1NDk2MjIgMTA3LjE1NzEzOTIxIDI5OS44ODcxMTU0OCAxMDcuMzk5MDc0NTUgQzMyMS4wOTAyNTc1MyAxMTUuMzk0MTA5ODIgMzQyLjI5NDg1MDcyIDEyMy4zODUyOTAxNyAzNjMuNSAxMzEuMzc1IEMzODcuODMxMDI5NzEgMTQwLjU0MjQ4NDAyIDQxMi4xNjEyNjM0MyAxNDkuNzEyMDczOTUgNDM2LjQ4OTUwMTk1IDE1OC44ODY5NjI4OSBDNDM4Ljk3ODY3NjIxIDE1OS44MjU2ODkzNiA0NDEuNDY3ODUyNjQgMTYwLjc2NDQxMDA3IDQ0My45NTcwMzEyNSAxNjEuNzAzMTI1IEM0NDUuODAwNzUwNjYgMTYyLjM5ODQyNTY3IDQ0NS44MDA3NTA2NiAxNjIuMzk4NDI1NjcgNDQ3LjY4MTcxNjkyIDE2My4xMDc3NzI4MyBDNDU0LjA4NjA4MzIxIDE2NS41MjI3NjM1NiA0NjAuNDkwNzU2NzQgMTY3LjkzNjkzNTE1IDQ2Ni44OTU5OTYwOSAxNzAuMzQ5NjA5MzggQzQ2Ny44ODMyNzY5OCAxNzAuNzIxNDkxNDggNDY3Ljg4MzI3Njk4IDE3MC43MjE0OTE0OCA0NjguODkwNTAyOTMgMTcxLjEwMDg4NjM0IEM0NzEuNTUzNjYyNTkgMTcyLjEwNDAwMjA3IDQ3NC4yMTY4MzE4OSAxNzMuMTA3MDkyMTYgNDc2Ljg4MDA0MzAzIDE3NC4xMTAwNzExOCBDNDg0LjAzODg5MjY4IDE3Ni44MDYxNjE3MyA0OTEuMTk3MDg3ODUgMTc5LjUwMzk1NzIzIDQ5OC4zNTMwMjczNCAxODIuMjA3NzYzNjcgQzUwMC44NDA4MTUyOCAxODMuMTQ3NjM3MDMgNTAzLjMyODYxMTE2IDE4NC4wODc0ODkzMyA1MDUuODE2NDA2MjUgMTg1LjAyNzM0Mzc1IEM1MDcuMDIxNDk4NDEgMTg1LjQ4MjY3OTkgNTA4LjIyNjU5MDU4IDE4NS45MzgwMTYwNSA1MDkuNDY4MjAwNjggMTg2LjQwNzE1MDI3IEM1MTYuMjEwODkxOTEgMTg4Ljk1MjUxMDc2IDUyMi45NTk0MDA2NyAxOTEuNDgxMzEwMSA1MjkuNzE1NTkxNDMgMTkzLjk5MDYxNTg0IEM1MzMuNjIzMzQ4MTcgMTk1LjQ0MzQzMTYgNTM3LjUyNzYyMDEgMTk2LjkwNTQ1NDcxIDU0MS40MzEzMDQ5MyAxOTguMzY5MTcxMTQgQzU0My4xOTExNDUwNCAxOTkuMDI2Mjk3NDggNTQ0Ljk1MjYyMjUzIDE5OS42NzkwNTk4MSA1NDYuNzE1OTExODcgMjAwLjMyNjg3Mzc4IEM1NjYuODMzNzkwNjIgMjA3LjcyMDQ1MTI4IDU4My44OTUzMDgxMyAyMTguNjMyMjkyNTYgNTk0LjI1IDIzOC4xMjUgQzYwMS43MDU2MzU1OSAyNTQuMjU4NTA2NTIgNjA0LjY2NDQ5MjMgMjc0LjE5MDQ4NzIxIDU5OS4xNTYyNSAyOTEuNTMxMjUgQzU5NC4xODcyNDM1MyAzMDMuNzUyMzE5OTYgNTg4LjUyNTc0NjE5IDMxMy4xODc5NDcgNTc5IDMyMi4zNzUgQzU3OC4wNTA2MDU0NyAzMjMuMzcwODAwNzggNTc4LjA1MDYwNTQ3IDMyMy4zNzA4MDA3OCA1NzcuMDgyMDMxMjUgMzI0LjM4NjcxODc1IEM1NzMuMDgwNjkzNjMgMzI4LjMxNzM5MjU4IDU2OC40OTExOTk5NiAzMzAuOTY5NTIwNDggNTYzLjU2MjUgMzMzLjU2MjUgQzU2Mi45NzMxNTY3NCAzMzMuODgzNzk4ODMgNTYyLjM4MzgxMzQ4IDMzNC4yMDUwOTc2NiA1NjEuNzc2NjExMzMgMzM0LjUzNjEzMjgxIEM1NTcuMTE0NjE5MjQgMzM2Ljg1NjAzMzE2IDU1Mi4yODM3OTg3IDMzOC4xMzczNTYwMiA1NDcuMjMwMTQ4MzIgMzM5LjMwNTY2NDA2IEM1NDYuNDgxNDkwMDggMzM5LjQ3OTgzMzIxIDU0NS43MzI4MzE4NCAzMzkuNjU0MDAyMzYgNTQ0Ljk2MTQ4NyAzMzkuODMzNDQ5MzYgQzUzNy4xNjUwNDczOSAzNDEuNjE0NzkwNjIgNTI5LjMxODg1MDI0IDM0My4wMjY1MTM1NSA1MjEuNDM4NzIwNyAzNDQuMzc4OTA2MjUgQzUxOS44NDQ3MzQ5MyAzNDQuNjU2NDA1MTkgNTE4LjI1MDgyNzYzIDM0NC45MzQzNTUxNCA1MTYuNjU2OTkxIDM0NS4yMTI3MDk0MyBDNTEyLjMyNjg0MjE5IDM0NS45Njc2NjE4NiA1MDcuOTk1NjQwOTYgMzQ2LjcxNjQwODUyIDUwMy42NjQyMzE3OCAzNDcuNDY0MDkwODIgQzQ5OC45NjgzMzY3MiAzNDguMjc1ODkyOSA0OTQuMjczNDkxNzYgMzQ5LjA5MzcyNjQ3IDQ4OS41Nzg1MjE3MyAzNDkuOTEwODU4MTUgQzQ4MS40MzY1MDU5OSAzNTEuMzI3MTUxMzQgNDczLjI5Mzg4NjkzIDM1Mi43Mzk5Mjk0NyA0NjUuMTUwODQ4MzkgMzU0LjE1MDMyOTU5IEM0NTIuMTQ5MjAwNTMgMzU2LjQwMjM1NTI4IDQzOS4xNDg4MzkwOCAzNTguNjYxNzUyMDIgNDI2LjE0ODY4MTY0IDM2MC45MjIzNjMyOCBDNDIxLjY2OTkxMTcxIDM2MS43MDExMDk2MiA0MTcuMTkxMTM1MDkgMzYyLjQ3OTgxNzQ4IDQxMi43MTIzNTY1NyAzNjMuMjU4NTE0NCBDNDExLjU5MjY5ODI5IDM2My40NTMxODg2OSA0MTAuNDczMDQwMDIgMzYzLjY0Nzg2Mjk3IDQwOS4zMTk0NTI3IDM2My44NDg0MzY0NyBDNDAxLjI2MzYxMjg2IDM2NS4yNDkwMTYxMSAzOTMuMjA3NjA2MyAzNjYuNjQ4NjM0MjMgMzg1LjE1MTUzNTAzIDM2OC4wNDc4ODIwOCBDMzQxLjY3NzA2NSAzNzUuNTk5MzYxNjkgMjk4LjIwOTIwOTYyIDM4My4xODg0OTk1NSAyNTQuNzQzMTk1NTMgMzkwLjc4ODQ4MjY3IEMyNDAuNTAyMjA5MSAzOTMuMjc4MzMyMjUgMjI2LjI2MDUyNDEgMzk1Ljc2NDE1MzE4IDIxMi4wMTg0NDc1MiAzOTguMjQ3NzU4ODcgQzIwMi41MDgyMTc4MSAzOTkuOTA2MzYxNDkgMTkyLjk5ODE5NzYgNDAxLjU2NjE2NDIzIDE4My40ODgyODEyNSA0MDMuMjI2NTYyNSBDMTgyLjQ2ODYwNzg3IDQwMy40MDQ1ODM0MiAxODEuNDQ4OTM0NDkgNDAzLjU4MjYwNDMzIDE4MC4zOTgzNjE5MiA0MDMuNzY2MDE5ODIgQzE1NS41ODg2MTQxNiA0MDguMDk4MDgzOTcgMTMwLjc4NTYyNjc2IDQxMi40NjQzMzc0MSAxMDUuOTk4NjUwNjEgNDE2LjkyNDk1ODM1IEMxMDEuMDgzNzk3MjIgNDE3LjgwOTM4MTQ4IDk2LjE2ODQxMTM1IDQxOC42OTA3ODE1NyA5MS4yNTI2MDkyNSA0MTkuNTY5OTE1NzcgQzkwLjU4ODIwMjcxIDQxOS42ODg3NjkyOCA4OS45MjM3OTYxNiA0MTkuODA3NjIyNzggODkuMjM5MjU2MDggNDE5LjkzMDA3NzkxIEM4Ni4wMDM2NjQzNSA0MjAuNTA4ODY4MDUgODIuNzY4MDM4MTQgNDIxLjA4NzQ2NDYgNzkuNTMyMzcwNTcgNDIxLjY2NTgzMDYxIEM3My41OTQ1ODU3OSA0MjIuNzI4NzA5MTMgNjcuNjU4NDcxNDYgNDIzLjc5OTQ4MTU1IDYxLjcyNTc5OTU2IDQyNC44OTA1NjM5NiBDLTQuNjgxMDU2NzcgNDM3LjEwMDQxMDQ5IC00LjY4MTA1Njc3IDQzNy4xMDA0MTA0OSAtMzEuOTAyMzQzNzUgNDE4LjM3ODkwNjI1IEMtNDcuMTYxMzY2NzkgNDA2LjcxNTI3NzE0IC01NS44MjM4NTk5NCAzOTAuNjc4MjIwMjcgLTU5LjY4NzUgMzcyLjA2MjUgQy02MS41MjIxOTI0IDM1MC40MTMxMjk2MyAtNTYuNTIxODg1MTQgMzMyLjA3MDQ0MDY3IC00My4yMzQzNzUgMzE0Ljg5NDUzMTI1IEMtMzIuOTQyNTU3MTYgMzAzLjI4OTI0NzE1IC0xOS43NTI3ODc3OCAyOTUuNDY2MzYxMiAtNC41ODA4MTA1NSAyOTIuMzQ3NTE4OTIgQy0zLjgwMzA3ODc5IDI5Mi4xODE0ODcyNyAtMy4wMjUzNDcwNCAyOTIuMDE1NDU1NjMgLTIuMjI0MDQ3NjYgMjkxLjg0NDM5MjcyIEM2LjAzOTg3NjYxIDI5MC4xMDYxNzM0MSAxNC4zNDc0Mzc3NSAyODguNjI3MDU5OTIgMjIuNjY3OTY4NzUgMjg3LjE4OTY5NzI3IEMyNC4zNTM1NTI2OCAyODYuODk1MjkwNjIgMjYuMDM5MDcyMTMgMjg2LjYwMDUxNDU0IDI3LjcyNDUzMzA4IDI4Ni4zMDU0MDQ2NiBDMzIuMjYyNzc3MSAyODUuNTExODQzOTIgMzYuODAxODg4OTQgMjg0LjcyMzM1NjIgNDEuMzQxMTc4ODkgMjgzLjkzNTgwMzY1IEM0Ni4xOTAzOTQ1NiAyODMuMDkzNTA5NSA1MS4wMzg3NTA2MSAyODIuMjQ2Mjk0NDcgNTUuODg3MjA3MDMgMjgxLjM5OTY0Mjk0IEM2NS4xMjk5ODU1MSAyNzkuNzg2MzcyODQgNzQuMzczNDgwNzYgMjc4LjE3NzI1MDAyIDgzLjYxNzI1OTk4IDI3Ni41Njk3MjQ5OCBDOTQuODUzNDg4MzEgMjc0LjYxNTUzNzkzIDEwNi4wODg4NzcxIDI3Mi42NTY1NDI5NSAxMTcuMzI0MjE4NzUgMjcwLjY5NzI2NTYyIEMxMjEuMzQ2Njc1NDEgMjY5Ljk5NTg1NTU4IDEyNS4zNjkxMzczIDI2OS4yOTQ0NzU1NSAxMjkuMzkxNjAxNTYgMjY4LjU5MzEwOTEzIEMxMzAuMzgyOTU5MjMgMjY4LjQyMDI0ODY3IDEzMS4zNzQzMTY5IDI2OC4yNDczODgyMiAxMzIuMzk1NzE1NzEgMjY4LjA2OTI4OTU3IEMxMzkuMzUzMjEyMjMgMjY2Ljg1NjIwMzE1IDE0Ni4zMTA4NTc4MyAyNjUuNjQzOTc0MTEgMTUzLjI2ODU1NDY5IDI2NC40MzIwMzczNSBDMTg5LjUxODk0MjM1IDI1OC4xMTcwNTYwNiAyMjUuNzYxMDk3NjEgMjUxLjc1NTUzMDExIDI2MiAyNDUuMzc1IEMyNTguODIyNjgzIDI0NC4wOTkwOTYxNCAyNTUuNjQ0Njc3MDkgMjQyLjgyNDkxNjc0IDI1Mi40NjY1NTI3MyAyNDEuNTUxMDI1MzkgQzI1MS41NzUxOTM3MSAyNDEuMTkzMDYxMjkgMjUwLjY4MzgzNDY5IDI0MC44MzUwOTcyIDI0OS43NjU0NjQ3OCAyNDAuNDY2Mjg1NzEgQzI0Mi44OTIyNjAxMyAyMzcuNzEyNzI4MzUgMjM1Ljk4NDE3Nzc2IDIzNS4wODI2Mzc4OCAyMjkuMDMxNzk5MzIgMjMyLjUzNDgyMDU2IEMyMjUuMzA4NjI0MDggMjMxLjE2OTE5NDczIDIyMS41ODk5MzM0NyAyMjkuNzkxNDQyMjQgMjE3Ljg3MTA5Mzc1IDIyOC40MTQwNjI1IEMyMTcuMDM2MDIyOTUgMjI4LjEwNDgxMzM5IDIxNi4yMDA5NTIxNSAyMjcuNzk1NTY0MjcgMjE1LjM0MDU3NjE3IDIyNy40NzY5NDM5NyBDMjAzLjcyOTUxMTU3IDIyMy4xNzAzNDI1OSAxOTIuMTQ1Nzg2MzUgMjE4Ljc5MDgwMjg0IDE4MC41NTk2NDI3OSAyMTQuNDE3NzEzMTcgQzE3OC42MTQxODI3NiAyMTMuNjgzNDE3MTMgMTc2LjY2ODY4NDE0IDIxMi45NDkyMjM0MSAxNzQuNzIzMTc1MDUgMjEyLjIxNTA1NzM3IEMxNjkuMzQyMzYxMDIgMjEwLjE4NDQ5NzExIDE2My45NjE2MDA1MiAyMDguMTUzNzk1MTMgMTU4LjU4MDk3ODM5IDIwNi4xMjI3MjY0NCBDMTQ0LjM0MDgwNDk4IDIwMC43NDc1Njk2OCAxMzAuMDk3MzM0OTMgMTk1LjM4MTE3MDI4IDExNS44NTM2OTg3MyAxOTAuMDE1MTk3NzUgQzEwOC42MDAyNTYwNCAxODcuMjgyNjAyODYgMTAxLjM0NzAwMTkgMTg0LjU0OTUwNzUyIDk0LjA5Mzc1IDE4MS44MTY0MDYyNSBDOTMuMzgwMTU2OTcgMTgxLjU0NzUxODM5IDkyLjY2NjU2Mzk1IDE4MS4yNzg2MzA1MiA5MS45MzEzNDY4OSAxODEuMDAxNTk0NTQgQzgyLjE3NDM0MzQ1IDE3Ny4zMjUwNTE1MiA3Mi40MTc0ODk4NiAxNzMuNjQ4MTExMTEgNjIuNjYwOTA1ODQgMTY5Ljk3MDQ1NTE3IEM2MC42NjU4MzkzNSAxNjkuMjE4NDU4MjUgNTguNjcwNzQzMDMgMTY4LjQ2NjU0MDUxIDU2LjY3NTY0MzkyIDE2Ny43MTQ2MzAxMyBDNTAuMjY2Njg4OSAxNjUuMjk5MDU0OTggNDMuODU4Mzg5NTMgMTYyLjg4MTc2MzcyIDM3LjQ1MTQxNjAyIDE2MC40NjA5Mzc1IEMyNi45MzM4MzM0OCAxNTYuNDg4MTYzMTggMTYuNDE1MDA1MzggMTUyLjUyMDIzMDkzIDUuODcyMTYxODcgMTQ4LjYxNDg4MzQyIEMxLjY5NTEyNjgxIDE0Ny4wNjcwMjY3IC0yLjQ3OTA3NDM4IDE0NS41MTE2MTE1OCAtNi42NTMwNzYxNyAxNDMuOTU1NTk2OTIgQy04LjU1MzkxMzExIDE0My4yNDk2MDE5NyAtMTAuNDU2MjAzMzcgMTQyLjU0NzUwNDM5IC0xMi4zNjAxMDc0MiAxNDEuODQ5ODIzIEMtMjcuMTUzMDU0OTUgMTM2LjQyODU0Nzc0IC0zOS42NDM0MjMzNSAxMzAuNjczODMxMzQgLTUxIDExOS4zNzUgQy01MS42OTg2NzE4OCAxMTguNzExMTMyODEgLTUyLjM5NzM0Mzc1IDExOC4wNDcyNjU2MyAtNTMuMTE3MTg3NSAxMTcuMzYzMjgxMjUgQy02Ni41MTc5NjU1MyAxMDMuNjc1NzE2MTQgLTcwLjUyMDc1NjQ0IDg1Ljg0NDk4MjE4IC03MC40MjAxNjYwMiA2Ny4zMDUxNzU3OCBDLTcwLjIxODY4NjM3IDU5LjA0Mzg2NjU3IC02OS4xOTgzNjk3IDUyLjA2NjMxNzYyIC02NiA0NC4zNzUgQy02NS41MjU2MjUgNDMuMjA5Njg3NSAtNjUuMDUxMjUgNDIuMDQ0Mzc1IC02NC41NjI1IDQwLjg0Mzc1IEMtNjAuNDU4NjIwMzUgMzEuNzY3ODYyMzEgLTU1LjA1NTg3MTIxIDI1LjI3NjEzNzE5IC00OCAxOC4zNzUgQy00Ny4zNjU3ODEyNSAxNy43MDg1NTQ2OSAtNDYuNzMxNTYyNSAxNy4wNDIxMDkzNyAtNDYuMDc4MTI1IDE2LjM1NTQ2ODc1IEMtMzMuNzYzNjkxODMgNC4yODYyMzQ0NyAtMTYuNjI4Mzg3MzYgMC4xODMwNjQ4MiAwIDAgWiAiIGZpbGw9IiMwMDAwMDAiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDIxMyw0MzIuNjI1KSIvPgo8L3N2Zz4K";
    $hook_suffix = add_menu_page("SnipVault", "SnipVault", "manage_options", "snipvault", ["SnipVault\Pages\Editor", "build_snipvault"], $icon);

    add_action("admin_head-{$hook_suffix}", ["SnipVault\Pages\Editor", "load_styles"]);
    add_action("admin_head-{$hook_suffix}", ["SnipVault\Pages\Editor", "load_scripts"]);
  }

  /**
   * snipvault settings page.
   *
   * Outputs the app holder
   */
  public static function build_snipvault()
  {
    // Enqueue the media library
    wp_enqueue_media();
    // Output the app
    echo "<div id='snipvault-app'></div>";
  }

  /**
   * snipvault styles.
   *
   * Loads main lp styles
   */
  public static function load_styles()
  {
    // Get plugin url
    $url = plugins_url("snipvault/");
    $style = $url . "app/dist/assets/styles/style.css";
    wp_enqueue_style("snipvault", $style, [], snipvault_plugin_version);
  }

  /**
   * snipvault scripts.
   *
   * Loads main lp scripts
   */
  public static function load_scripts()
  {
    self::output_script_attributes();

    // Get plugin url
    $url = plugins_url("snipvault/");
    $script_name = Scripts::get_base_script_path("snipvault.js");

    // Setup script object
    $builderScript = [
      "id" => "snipvault-js",
      "src" => $url . "app/dist/{$script_name}",
      "type" => "module",
    ];

    // Print tag
    wp_print_script_tag($builderScript);
  }

  /**
   * Return global options
   *
   */
  public static function return_global_options()
  {
    if (empty(self::$options)) {
      self::$options = get_option("snipvault_settings", []);
    }

    return self::$options;
  }

  /**
   * SnipVault scripts.
   *
   * Loads main snipvault scripts
   */
  public static function output_script_attributes()
  {
    $url = plugins_url("snipvault/");
    $rest_base = get_rest_url();
    $rest_nonce = wp_create_nonce("wp_rest");
    $admin_url = get_admin_url();
    $login_url = wp_login_url();
    global $wp_post_types;

    // Get the current user object
    $current_user = wp_get_current_user();
    $first_name = $current_user->first_name;
    $roles = (array) $current_user->roles;
    $options = self::return_global_options();
    $formatArgs = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK;
    $manageOptions = current_user_can("manage_options") ? "true" : "false";

    // Remove the 'license_key' key
    unset($options["license_key"]);
    unset($options["instance_id"]);

    // If first name is empty, fall back to display name
    if (empty($first_name)) {
      $first_name = $current_user->display_name;
    }

    // Get the user's email
    $email = $current_user->user_email;

    $frontPage = is_admin() ? "false" : "true";
    $mime_types = array_values(get_allowed_mime_types());

    // Ajax
    $nonce = wp_create_nonce("snirvault_wpcli_nonce");
    $ajaxURL = admin_url("admin-ajax.php");

    global $wp_roles;
    $roles = $wp_roles->get_names();

    $formattedRoles = [];
    foreach ($roles as $key => $role) {
      $formattedRoles[] = [
        "value" => $key,
        "label" => $role,
      ];
    }

    // Setup script object
    $builderScript = [
      "id" => "snipvault-data",
      "type" => "module",
      "plugin-base" => esc_url($url),
      "rest-base" => esc_url($rest_base),
      "rest-nonce" => esc_attr($rest_nonce),
      "admin-url" => esc_url($admin_url),
      "login-url" => esc_url($login_url),
      "user-id" => esc_attr(get_current_user_id()),
      "user-roles" => esc_attr(json_encode($roles)),
      "snipvault-settings" => esc_attr(json_encode($options, $formatArgs)),
      "user-name" => esc_attr($first_name),
      "can-manage-options" => esc_attr($manageOptions),
      "user-email" => esc_attr($email),
      "site-url" => esc_url(get_home_url()),
      "front-page" => esc_url($frontPage),
      "post_types" => esc_attr(json_encode($wp_post_types)),
      "mime_types" => esc_attr(json_encode($mime_types)),
      //"signing-key" => esc_attr(self::get_signing_key()),
      "app-version" => esc_attr(snipvault_plugin_version),
      "ajax-url" => $ajaxURL,
      "ajax-nonce" => $nonce,
      "formatted-roles" => esc_attr(json_encode($formattedRoles)),
    ];

    // Print tag
    wp_print_script_tag($builderScript);
  }

  /**
   * Get or generate the signing key
   */
  private static function get_signing_key()
  {
    $key = get_option("snipvault_secure_snippets_key");
    if (!$key) {
      $key = bin2hex(random_bytes(32));
      update_option("snipvault_secure_snippets_key", $key);
    }
    return $key;
  }
}
