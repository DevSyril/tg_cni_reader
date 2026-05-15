<?php

namespace TgDocumentProcessor\Drivers\Cni\Models;

class CniCard
{
    public CniFront $front;
    public CniBack $back;
    public ?bool $isExpired;
    public ?bool $isInvalid;
}
