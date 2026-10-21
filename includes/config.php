<?php
/* ===== EduShare configuration — edit to match your XAMPP setup ===== */
const BASE_URL = '/edushare';
// folder name inside htdocs
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_MAIN = 'edushare_main';
// users, items, requests, notifications
const DB_ADMIN = 'edushare_admin';
// administrators + admin activity log
const SITE_NAME = 'EduShare';
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
function db(string $which = 'main') : PDO {
    static $pool = [];
    if (!isset($pool[$which])) {
        $name = $which === 'admin' ? DB_ADMIN : DB_MAIN;
        $pool[$which] = new PDO('mysql:host=' . DB_HOST . ';dbname=' . $name . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,]);
    }
    return $pool[$which];
}
