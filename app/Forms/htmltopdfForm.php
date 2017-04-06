<?php

namespace App\Forms;

use Kris\LaravelFormBuilder\Form;

class htmltopdfForm extends Form
{
    public function buildForm()
    {
        $this
            ->add('zip', 'file');
    }
}
