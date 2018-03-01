<?php
namespace SM\View;

interface ResourceInterface
{
	public function getTemplateId($tpl);
	public function getTemplate($tpl);
	public function getTimestamp($tpl);
}
