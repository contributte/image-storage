<?php

namespace Contributte\ImageStorage\Latte;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Extension;


class LatteExtension extends Extension
{
	public function getTags(): array
	{
		return [
			'img' => [$this, 'tagImg'],
			'imgAbs' => [$this, 'tagImgAbs'],
			'n:img' => [$this, 'attrImg'],
			'n:imgAbs' => [$this, 'attrImgAbs'],
			'imgLink' => [$this, 'linkImg'],
			'imgLinkAbs' => [$this, 'linkImgAbs'],
		];
	}


	public function tagImg(Tag $tag): Node
	{
		$tag->parser->stream->tryConsume(',');
		$args = $tag->parser->parseArguments();
		return new AuxiliaryNode(
			fn(PrintContext $context) => $context->format('$_img = $imageStorage->fromIdentifier(%node); echo "<img src=\"" . $basePath . "/" . $_img->createLink() . "\">";', $args)
		);
	}


	public function tagImgAbs(Tag $tag): Node
	{
		$args = $tag->parser->parseArguments();
		return new AuxiliaryNode(
			fn(PrintContext $context) => $context->format('$_img = $imageStorage->fromIdentifier(%node); echo "<img src=\"" . $baseUrl . "/" . $_img->createLink() . "\">";', $args)
		);
	}


	public function attrImg(Tag $tag): Node
	{
		$args = $tag->parser->parseArguments();
		return new AuxiliaryNode(
			fn(PrintContext $context) => $context->format('$_img = $imageStorage->fromIdentifier(%node); echo \' src="\' . $basePath . "/" . $_img->createLink() . \'"\';', $args)
		);
	}


	public function attrImgAbs(Tag $tag): Node
	{
		$args = $tag->parser->parseArguments();
		return new AuxiliaryNode(
			fn(PrintContext $context) => $context->format('$_img = $imageStorage->fromIdentifier(%node); echo \' src="\' . $baseUrl . "/" . $_img->createLink() . \'"\';', $args)
		);
	}


	public function linkImg(Tag $tag): Node
	{
		$args = $tag->parser->parseArguments();
		return new AuxiliaryNode(
			fn(PrintContext $context) => $context->format('$_img = $imageStorage->fromIdentifier(%node); echo $basePath . "/" . $_img->createLink();', $args)
		);
	}


	public function linkImgAbs(Tag $tag): Node
	{
		$args = $tag->parser->parseArguments();
		return new AuxiliaryNode(
			fn(PrintContext $context) => $context->format('$_img = $imageStorage->fromIdentifier(%node); echo $baseUrl . "/" . $_img->createLink();', $args)
		);
	}
}
