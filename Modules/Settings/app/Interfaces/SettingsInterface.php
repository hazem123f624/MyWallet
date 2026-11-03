<?php

namespace Modules\Settings\Interfaces;

interface SettingsInterface
{
    public function data($request, $id = '*');

    public function save($request, $id = '');

    public function delete($data);

    public function switchActive($id);
}
