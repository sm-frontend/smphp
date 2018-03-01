<?php
namespace SM\Upload;

interface StorageInterface
{
	public function upload(FileInfoInterface $fileInfo);
}
