<?php

namespace App\Services\Documentations\Interfaces;

interface DocumentationsInterface
{
    public function data($request, $id = '*');

    public function save($request, $id = '');

    public function delete($id);

    //    public function getApiTestedStatus();
}
