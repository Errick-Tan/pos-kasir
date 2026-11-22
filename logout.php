<?php
require __DIR__ . '/includes/bootstrap.php';
require_post();
auth_logout();
session_start();
flash('ok', 'Anda sudah keluar.');
redirect('login.php');
