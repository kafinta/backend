<?php

namespace App\Http\Controllers;
use App\Traits\JsonExceptionHandlerTrait;
use App\Traits\ReferenceGeneratorTrait;
use Illuminate\Http\Request;

class ImprovedController extends Controller
{
    use JsonExceptionHandlerTrait, ReferenceGeneratorTrait;
}