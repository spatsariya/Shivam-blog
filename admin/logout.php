<?php
session_start();
include '../includes/functions.php';

logout();
header('Location: ../index.php');
exit;

