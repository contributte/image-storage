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

		$set->addMacro('imgLink', [$set, 'linkImg']);

		return $set;
	}


	public function tagImg(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo "<img src=\"" . $basePath . "/" . $_img->createLink() . "\">";');
	}


	public function attrImg(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo \' src="\' . $basePath . "/" . $_img->createLink() . \'"\'');
	}


	public function linkImg(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		return $writer->write('$_img = $imageStorage->fromIdentifier(%node.array); echo $basePath . "/" . $_img->createLink()');
	}

}
