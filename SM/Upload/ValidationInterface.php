<?php
namespace SM\Upload;

interface ValidationInterface
{
	public function validate(FileInfoInterface $fileInfo);
}
