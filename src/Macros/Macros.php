<?php

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ImageStorage\Macros;

use Latte;

class Macros extends Latte\Macros\MacroSet
{

	public static function install(Latte\Compiler $compiler)
	{
		$set = new static($compiler);

		$set->addMacro('img', [$set, 'tagImg'], NULL, [$set, 'attrImg']);
		$set->addMacro('imgAbs', [$set, 'tagImgAbs'], NULL, [$set, 'attrImgAbs']);

		$set->addMacro('imgLink', [$set, 'linkImg']);
		$set->addMacro('imgLinkAbs', [$set, 'linkImgAbs']);

		return $set;
	}


	public function tagImg(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo "<img src=\"" . $basePath . "/" . $_img->createLink() . "\">";');
	}


	public function tagImgAbs(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo "<img src=\"" . $baseUrl . "/" . $_img->createLink() . "\">";');
	}


	public function attrImg(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo \' src="\' . $basePath . "/" . $_img->createLink() . \'"\'');
	}


	public function attrImgAbs(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo \' src="\' . $baseUrl . "/" . $_img->createLink() . \'"\'');
	}


	public function linkImg(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo $basePath . "/" . $_img->createLink()');
	}


	public function linkImgAbs(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo $baseUrl . "/" . $_img->createLink()');
	}

}
