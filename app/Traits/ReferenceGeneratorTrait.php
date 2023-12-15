<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait ReferenceGeneratorTrait
{

    public function referenceGenerator($length = 10) {
        return substr(str_shuffle(
            str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )
        ),1,$length);
    }

    public function generateUid(){
        return $this->referenceGenerator(10). '-' . $this->referenceGenerator(6) . '-' . $this->referenceGenerator(8);
    }

    public function generateUuid(){
        return (string) Str::uuid();
    }

}