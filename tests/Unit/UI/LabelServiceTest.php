<?php

namespace Tests\Unit\UI;

use Tests\TestCase;
use MCAG\Service\UI\LabelService;

class LabelServiceTest extends TestCase
{
    public function test_it_defaults_to_standard_vocabulary()
    {
        $service = new LabelService('standard');

        // If key missing, returns fallback (the key itself)
        $this->assertEquals('employee', $service->get('employee', 'employee'));
    }

    public function test_it_loads_healthcare_vertical()
    {
        $service = new LabelService('healthcare');

        $this->assertEquals('Sanitario', $service->get('employee_single'));
        $this->assertEquals('Reparto', $service->get('department_single'));
    }

    public function test_it_loads_logistics_vertical()
    {
        $service = new LabelService('logistics');

        $this->assertEquals('Autista', $service->get('employee_single'));
        $this->assertEquals('Hub', $service->get('department_single'));
    }

    public function test_template_helper_closure()
    {
        $service = new LabelService('healthcare');
        $helper = $service->getTemplateHelper();

        $this->assertEquals('Sanitario', $helper('employee_single'));
        $this->assertEquals('unknown_key', $helper('unknown_key'));
    }
}
