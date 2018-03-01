<?php
namespace SM\Upload;

interface FileInfoInterface
{
	public function getPathname();
	public function getName();
	public function setName($name);
	public function getExtension();
	public function setExtension($extension);
	public function getUrl();
	public function setUrl($url);
	public function getBase64();
	public function setBase64($base64);
	public function getNameWithExtension();
	public function getMimetype();
	public function getSize();
	public function getMd5();
	public function getHash();
	public function getContents();
	public function isUploadedFile();
}
