<?php declare(strict_types = 1);

namespace Contributte\ImageStorage\Macros;

use Latte;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\PhpWriter;

class Macros extends Latte\Macros\MacroSet
{

	public static function install(Compiler $compiler): Macros
	{
		$set = new static($compiler);

		$set->addMacro('img', [$set, 'tagImg'], null, [$set, 'attrImg']);
		$set->addMacro('imgAbs', [$set, 'tagImgAbs'], null, [$set, 'attrImgAbs']);

		$set->addMacro('imgLink', [$set, 'linkImg']);
		$set->addMacro('imgLinkAbs', [$set, 'linkImgAbs']);

		return $set;
	}


	public function tagImg(MacroNode $node, PhpWriter $writer): string
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo "<img src=\"" . $basePath . "/" . $_img->createLink() . "\">";');
	}


	public function tagImgAbs(MacroNode $node, PhpWriter $writer): string
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo "<img src=\"" . $baseUrl . "/" . $_img->createLink() . "\">";');
	}


	public function attrImg(MacroNode $node, PhpWriter $writer): string
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo \' src="\' . $basePath . "/" . $_img->createLink() . \'"\'');
	}


	public function attrImgAbs(MacroNode $node, PhpWriter $writer): string
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo \' src="\' . $baseUrl . "/" . $_img->createLink() . \'"\'');
	}


	public function linkImg(MacroNode $node, PhpWriter $writer): string
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo $basePath . "/" . $_img->createLink()');
	}


	public function linkImgAbs(MacroNode $node, PhpWriter $writer): string
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo $baseUrl . "/" . $_img->createLink()');
	}

}
