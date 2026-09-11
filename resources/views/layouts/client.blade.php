{{--
    Client dashboard layout — AdminLTE shell, Vue islands for complex widgets
    (docs/05 § 2). Sidebar comes from config/menu.php "client".

    `sidebar-collapse` ships the sidebar closed: clients edit invitations on
    phones, where the content matters more than the nav.
--}}
@extends('layouts.adminlte', ['menuKey' => 'client', 'bodyClass' => 'sidebar-collapse'])
