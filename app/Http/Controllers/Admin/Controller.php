<?php namespace Forret\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Forret\Http\Controllers\Common\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends BaseController
{
    use DispatchesCommands, ValidatesRequests;
}