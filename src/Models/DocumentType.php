<?php

namespace TgDocumentProcessor\Models;

enum DocumentType: string
{
    case CNI = 'cni';
    case PASSPORT = 'passport';
    case DRIVER_LICENSE = 'driver_license';
}
