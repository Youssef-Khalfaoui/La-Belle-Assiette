<?php

$pageTitle = $pageTitle ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>La Belle Assiette</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="../images/logo/favicon.svg">
<link rel="stylesheet" href="../css/style.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    body {
      background: #F0EDE8;
      margin: 0;
      font-family: 'DM Sans', sans-serif;
      overflow-x: hidden;
    }

    .admin-layout {
      display: grid;
      grid-template-columns: 240px 1fr;
      min-height: 100vh;
    }

    .admin-sidebar {
      background: var(--noir);
    }
    .admin-sidebar-inner {
      position: sticky;
      top: 0;
      height: 100vh;
      overflow-y: auto;
      padding: 1.5rem 1rem;
      display: flex;
      flex-direction: column;
    }

    .admin-logo {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.25rem 0.5rem 1.5rem;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      margin-bottom: 1.25rem;
      flex-shrink: 0;
    }

    .admin-logo-icon {
      width: 40px;
      height: 40px;
      background: var(--rouge);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      color: #fff;
      flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(232,68,42,0.4);
    }

    .admin-logo-name {
      font-family: 'Playfair Display', serif;
      color: var(--or);
      font-size: 1rem;
      font-style: italic;
      line-height: 1.2;
    }

    .admin-logo-sub {
      color: rgba(255,255,255,0.4);
      font-size: 0.68rem;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-top: 2px;
    }

    .admin-nav {
      display: flex;
      flex-direction: column;
      flex: 1;
    }

    .admin-nav a {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.7rem 1rem;
      border-radius: 10px;
      color: rgba(255,255,255,0.65);
      font-size: 0.88rem;
      font-weight: 500;
      transition: background 0.18s, color 0.18s;
      margin-bottom: 0.2rem;
      text-decoration: none;
    }

    .admin-nav .nav-icon {
      width: 20px;
      text-align: center;
      font-size: 0.95rem;
      flex-shrink: 0;
    }

    .admin-nav a:hover {
      background: rgba(255,255,255,0.09);
      color: #fff;
    }

    .admin-nav a.actif {
      background: var(--rouge);
      color: #fff;
      box-shadow: 0 4px 14px rgba(232,68,42,0.35);
    }

    .nav-divider {
      height: 1px;
      background: rgba(255,255,255,0.08);
      margin: 0.75rem 0.5rem;
    }

    .admin-nav .nav-secondary {
      color: rgba(255,255,255,0.5);
    }

    .admin-nav .nav-logout {
      color: rgba(255,110,80,0.8);
      margin-top: auto;
    }

    .admin-nav .nav-logout:hover {
      background: rgba(232,68,42,0.15);
      color: #ff7c6a;
    }

    .admin-main {
      padding: 2rem 2.5rem;
      overflow-x: hidden;
      min-width: 0;
    }

    .admin-page-title {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 0.4rem;
    }

    .admin-page-title h2 {
      font-family: 'Playfair Display', serif;
      font-size: 1.7rem;
      margin: 0;
    }

    .admin-page-title .title-icon {
      width: 42px;
      height: 42px;
      background: var(--rouge-pale);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--rouge);
      font-size: 1.1rem;
      flex-shrink: 0;
    }

    .admin-page-subtitle {
      color: var(--gris);
      font-size: 0.88rem;
      margin-bottom: 1.75rem;
    }

    .admin-card {
      background: var(--blanc);
      border-radius: var(--rayon);
      border: 1px solid var(--bordure);
      overflow: hidden;
      margin-bottom: 1.5rem;
    }

    .admin-card-header {
      padding: 1.1rem 1.5rem;
      border-bottom: 1px solid var(--bordure);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
    }

    .admin-card-header h3 {
      font-size: 1rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .admin-card-header h3 i {
      color: var(--rouge);
    }

    .admin-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.875rem;
    }

    .admin-table thead tr {
      background: var(--gris-clair);
    }

    .admin-table th {
      padding: 0.9rem 1.25rem;
      text-align: left;
      color: var(--gris);
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      font-weight: 700;
      white-space: nowrap;
    }

    .admin-table td {
      padding: 0.9rem 1.25rem;
      border-bottom: 1px solid var(--bordure);
      vertical-align: middle;
    }

    .admin-table tbody tr:last-child td {
      border-bottom: none;
    }

    .admin-table tbody tr:hover td {
      background: #fafaf9;
    }

    .btn-action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      padding: 0.42rem 0.9rem;
      border-radius: 8px;
      font-size: 0.82rem;
      font-weight: 600;
      font-family: 'DM Sans', sans-serif;
      border: none;
      cursor: pointer;
      transition: background 0.18s, transform 0.15s, box-shadow 0.15s;
      text-decoration: none;
      white-space: nowrap;
    }

    .btn-action:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }

    .btn-action:active { transform: translateY(0); }

    .btn-delete {
      background: #fff0ee;
      color: #d63c2c;
      border: 1.5px solid #fcd0cb;
    }

    .btn-delete:hover {
      background: #d63c2c;
      color: #fff;
      border-color: #d63c2c;
    }

    .btn-primary {
      background: var(--rouge);
      color: #fff;
    }

    .btn-primary:hover { background: #c73520; }

    .btn-secondary {
      background: var(--gris-clair);
      color: var(--gris-fonce);
      border: 1.5px solid var(--bordure);
    }

    .btn-secondary:hover {
      background: var(--bordure);
      color: var(--noir);
    }

    .btn-gold {
      background: var(--or);
      color: var(--noir);
    }

    .btn-gold:hover { background: #c8962a; }

    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
    }

    .empty-state-icon {
      font-size: 3rem;
      color: var(--gris);
      margin-bottom: 1rem;
    }

    @media (max-width: 860px) {
      .admin-layout { grid-template-columns: 1fr; }
      .admin-sidebar-inner { height: auto; position: static; flex-direction: row; flex-wrap: wrap; }
      .admin-main { padding: 1.25rem; }
    }
  </style>
  <script src="../js/scroll-top.js" defer></script>
