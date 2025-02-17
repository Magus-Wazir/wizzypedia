<?php
/**
 * Static analysis tool for MediaWiki extensions.
 *
 * To use, add this file to your phan plugins list.
 *
 * Copyright (C) 2017  Brian Wolff <bawolff@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

use ast\Node;
use Phan\AST\UnionTypeVisitor;
use Phan\CodeBase;
use Phan\Exception\CodeBaseException;
use Phan\Language\Context;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use SecurityCheckPlugin\FunctionTaintedness;
use SecurityCheckPlugin\MWPreVisitor;
use SecurityCheckPlugin\MWVisitor;
use SecurityCheckPlugin\SecurityCheckPlugin;
use SecurityCheckPlugin\Taintedness;
use SecurityCheckPlugin\TaintednessVisitor;

class MediaWikiSecurityCheckPlugin extends SecurityCheckPlugin {
	/**
	 * @inheritDoc
	 */
	public static function getPostAnalyzeNodeVisitorClassName(): string {
		return MWVisitor::class;
	}

	/**
	 * @inheritDoc
	 */
	public static function getPreAnalyzeNodeVisitorClassName(): string {
		return MWPreVisitor::class;
	}

	/**
	 * @inheritDoc
	 */
	protected function getCustomFuncTaints(): array {
		$selectWrapper = [
			self::SQL_EXEC_TAINT,
			// List of fields. MW does not escape things like COUNT(*)
			self::SQL_EXEC_TAINT,
			// Where conditions
			self::SQL_NUMKEY_EXEC_TAINT,
			// the function name doesn't seem to be escaped
			self::SQL_EXEC_TAINT,
			// OPTIONS. Its complicated. HAVING is like WHERE
			// This is treated as special case
			self::NO_TAINT,
			// Join conditions. This is treated as special case
			self::NO_TAINT,
			// What should DB results be considered?
			'overall' => self::YES_TAINT
		];

		$linkRendererMethods = [
			/* target */
			self::NO_TAINT,
			/* text (using HtmlArmor) */
			self::ESCAPES_HTML,
			// The array keys for this aren't escaped (!)
			/* attribs */
			self::NO_TAINT,
			/* query */
			self::NO_TAINT,
			'overall' => self::ESCAPED_TAINT
		];

		$shellCommandOutput = [
			// This is a bit unclear. Most of the time
			// you should probably be escaping the results
			// of a shell command, but not all the time.
			'overall' => self::YES_TAINT
		];

		return [
			// Note, at the moment, this checks where the function
			// is implemented, so you can't use IDatabase.
			'\Wikimedia\Rdbms\Database::query' => [
				self::SQL_EXEC_TAINT,
				// What should DB results be considered?
				'overall' => self::YES_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::query' => [
				self::SQL_EXEC_TAINT,
				// What should DB results be considered?
				'overall' => self::YES_TAINT
			],
			'\Wikimedia\Rdbms\IMaintainableDatabase::query' => [
				self::SQL_EXEC_TAINT,
				// What should DB results be considered?
				'overall' => self::YES_TAINT
			],
			'\Wikimedia\Rdbms\DBConnRef::query' => [
				self::SQL_EXEC_TAINT,
				// What should DB results be considered?
				'overall' => self::YES_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::select' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::select' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::select' => $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::select' => $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::selectField' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::selectField' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectField' => $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::selectField' => $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::selectFieldValues' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::selectFieldValues' => $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::selectFieldValues' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectFieldValues' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::selectSQLText' => [
					'overall' => self::YES_TAINT & ~self::SQL_TAINT
				] + $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::selectSQLText' => [
					'overall' => self::YES_TAINT & ~self::SQL_TAINT
				] + $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::selectSQLText' => [
					'overall' => self::YES_TAINT & ~self::SQL_TAINT
				] + $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectSQLText' => [
					'overall' => self::YES_TAINT & ~self::SQL_TAINT
				] + $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::selectRowCount' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::selectRowCount' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectRowCount' => $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::selectRowCount' => $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::selectRow' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::selectRow' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectRow' => $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::selectRow' => $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::delete' => [
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IMaintainableDatabase::delete' => [
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\Database::delete' => [
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DBConnRef::delete' => [
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::insert' => [
				// table name
				self::SQL_EXEC_TAINT,
				// FIXME This doesn't correctly work
				// when inserting multiple things at once.
				self::SQL_NUMKEY_EXEC_TAINT,
				// method name
				self::SQL_EXEC_TAINT,
				// options. They are not escaped
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IMaintainableDatabase::insert' => [
				// table name
				self::SQL_EXEC_TAINT,
				// FIXME This doesn't correctly work
				// when inserting multiple things at once.
				self::SQL_NUMKEY_EXEC_TAINT,
				// method name
				self::SQL_EXEC_TAINT,
				// options. They are not escaped
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\Database::insert' => [
				// table name
				self::SQL_EXEC_TAINT,
				// Insert values. The keys names are unsafe.
				// Unclear how well this works for the multi case.
				self::SQL_NUMKEY_EXEC_TAINT,
				// method name
				self::SQL_EXEC_TAINT,
				// options. They are not escaped
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DBConnRef::insert' => [
				// table name
				self::SQL_EXEC_TAINT,
				// Insert values. The keys names are unsafe.
				// Unclear how well this works for the multi case.
				self::SQL_NUMKEY_EXEC_TAINT,
				// method name
				self::SQL_EXEC_TAINT,
				// options. They are not escaped
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::update' => [
				// table name
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				// method name
				self::SQL_EXEC_TAINT,
				// options. They are validated
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IMaintainableDatabase::update' => [
				// table name
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				// method name
				self::SQL_EXEC_TAINT,
				// options. They are validated
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\Database::update' => [
				// table name
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				// method name
				self::SQL_EXEC_TAINT,
				// options. They are validated
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DBConnRef::update' => [
				// table name
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				// method name
				self::SQL_EXEC_TAINT,
				// options. They are validated
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			// This is subpar, as addIdentifierQuotes isn't always
			// the right type of escaping.
			'\Wikimedia\Rdbms\Database::addIdentifierQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DatabaseMysqlBase::addIdentifierQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DatabaseMssql::addIdentifierQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::addIdentifierQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DBConnRef::addIdentifierQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\Database::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DBConnRef::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DatabaseMysqlBase::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DatabaseMssql::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IMaintainableDatabase::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DatabasePostgres::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DatabaseSqlite::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\Database::buildLike' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				( self::YES_TAINT & ~self::SQL_TAINT ) | self::VARIADIC_PARAM,
				'overall' => self::NO_TAINT
			],
			// makeList is special cased in MWVistor::checkMakeList
			// so simply disable auto-taint detection here.
			'\Wikimedia\Rdbms\IDatabase::makeList' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			// '\Message::__construct' => self::YES_TAINT,
			// '\wfMessage' => self::YES_TAINT,
			'\Message::plain' => [ 'overall' => self::YES_TAINT ],
			'\Message::text' => [ 'overall' => self::YES_TAINT ],
			'\Message::parseAsBlock' => [ 'overall' => self::ESCAPED_TAINT ],
			'\Message::parse' => [ 'overall' => self::ESCAPED_TAINT ],
			'\Message::__toString' => [ 'overall' => self::ESCAPED_TAINT ],
			'\Message::escaped' => [ 'overall' => self::ESCAPED_TAINT ],
			'\Message::rawParams' => [
				// The argument should be already escaped.
				self::HTML_EXEC_TAINT | self::VARIADIC_PARAM,
				// meh, not sure how right the overall is.
				'overall' => self::HTML_TAINT
			],
			// AddItem should also take care of addGeneral and friends.
			'\StripState::addItem' => [
				// type
				self::NO_TAINT,
				// marker
				self::NO_TAINT,
				// contents
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			// FIXME Doesn't handle array args right.
			'\wfShellExec' => [
				self::SHELL_EXEC_TAINT | self::ARRAY_OK,
				'overall' => self::YES_TAINT
			],
			'\wfShellExecWithStderr' => [
				self::SHELL_EXEC_TAINT | self::ARRAY_OK,
				'overall' => self::YES_TAINT
			],
			'\wfEscapeShellArg' => [
				( self::YES_TAINT & ~self::SHELL_TAINT ) | self::VARIADIC_PARAM,
				'overall' => self::NO_TAINT
			],
			'\MediaWiki\Shell\Shell::escape' => [
				( self::YES_TAINT & ~self::SHELL_TAINT ) | self::VARIADIC_PARAM,
				'overall' => self::NO_TAINT
			],
			'\MediaWiki\Shell\Command::unsafeParams' => [
				self::SHELL_EXEC_TAINT | self::VARIADIC_PARAM,
				'overall' => self::NO_TAINT
			],
			'\MediaWiki\Shell\Result::getStdout' => $shellCommandOutput,
			'\MediaWiki\Shell\Result::getStderr' => $shellCommandOutput,
			// Methods from wikimedia/Shellbox
			'\Shellbox\Shellbox::escape' => [
				( self::YES_TAINT & ~self::SHELL_TAINT ) | self::VARIADIC_PARAM,
				'overall' => self::NO_TAINT
			],
			'\Shellbox\Command\Command::unsafeParams' => [
				self::SHELL_EXEC_TAINT | self::VARIADIC_PARAM,
				'overall' => self::NO_TAINT
			],
			'\Shellbox\Command\UnboxedResult::getStdout' => $shellCommandOutput,
			'\Shellbox\Command\UnboxedResult::getStderr' => $shellCommandOutput,
			'\Html::rawElement' => [
				self::YES_TAINT,
				self::ESCAPES_HTML,
				self::YES_TAINT,
				'overall' => self::ESCAPED_TAINT
			],
			'\Html::element' => [
				self::YES_TAINT,
				self::ESCAPES_HTML,
				self::ESCAPES_HTML,
				'overall' => self::ESCAPED_TAINT
			],
			'\Xml::tags' => [
				self::YES_TAINT,
				self::ESCAPES_HTML,
				self::YES_TAINT,
				'overall' => self::ESCAPED_TAINT
			],
			'\Xml::element' => [
				self::YES_TAINT,
				self::ESCAPES_HTML,
				self::ESCAPES_HTML,
				'overall' => self::ESCAPED_TAINT
			],
			'\Xml::encodeJsVar' => [
				self::ESCAPES_HTML,
				/* pretty */
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Xml::encodeJsCall' => [
				/* func name. unescaped */
				self::YES_TAINT,
				self::ESCAPES_HTML,
				/* pretty */
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			'\OutputPage::addHeadItem' => [
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\OutputPage::addHTML' => [
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\OutputPage::prependHTML' => [
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\OutputPage::addInlineStyle' => [
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\OutputPage::parse' => [ 'overall' => self::NO_TAINT, ],
			'\Sanitizer::escapeHtmlAllowEntities' => [
				( self::YES_TAINT & ~self::HTML_TAINT ),
				'overall' => self::ESCAPED_TAINT
			],
			'\Sanitizer::safeEncodeAttribute' => [
				self::ESCAPES_HTML,
				'overall' => self::ESCAPED_TAINT
			],
			'\Sanitizer::encodeAttribute' => [
				self::ESCAPES_HTML,
				'overall' => self::ESCAPED_TAINT
			],
			'\WebRequest::getGPCVal' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getRawVal' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getVal' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getArray' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getIntArray' => [ 'overall' => self::NO_TAINT, ],
			'\WebRequest::getInt' => [ 'overall' => self::NO_TAINT, ],
			'\WebRequest::getIntOrNull' => [ 'overall' => self::NO_TAINT, ],
			'\WebRequest::getFloat' => [ 'overall' => self::NO_TAINT, ],
			'\WebRequest::getBool' => [ 'overall' => self::NO_TAINT, ],
			'\WebRequest::getFuzzyBool' => [ 'overall' => self::NO_TAINT, ],
			'\WebRequest::getCheck' => [ 'overall' => self::NO_TAINT, ],
			'\WebRequest::getText' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getValues' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getValueNames' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getQueryValues' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getRawQueryString' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getRawPostString' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getRawInput' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getCookie' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getGlobalRequestURL' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getRequestURL' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getFullRequestURL' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getAllHeaders' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getHeader' => [ 'overall' => self::YES_TAINT, ],
			'\WebRequest::getAcceptLang' => [ 'overall' => self::YES_TAINT, ],
			'\HtmlArmor::__construct' => [
				// The argument should be already escaped.
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			// Due to limitations in how we handle list()
			// elements, hard code CommentStore stuff.
			'\CommentStore::insert' => [
				'overall' => self::NO_TAINT
			],
			'\CommentStore::getJoin' => [
				'overall' => self::NO_TAINT
			],
			'\CommentStore::insertWithTempTable' => [
				'overall' => self::NO_TAINT
			],
			// TODO FIXME, Why couldn't it figure out
			// that this is safe on its own?
			// It seems that it has issue with
			// the url query parameters.
			'\Linker::linkKnown' => [
				/* target */
				self::NO_TAINT,
				/* raw html text, should be already escaped */
				self::HTML_EXEC_TAINT,
				// The array keys for this aren't escaped (!)
				/* customAttribs */
				self::NO_TAINT,
				/* query */
				self::NO_TAINT,
				/* options. All are safe */
				self::NO_TAINT,
				'overall' => self::ESCAPED_TAINT
			],
			'\MediaWiki\Linker\LinkRenderer::buildAElement' => $linkRendererMethods,
			'\MediaWiki\Linker\LinkRenderer::makeLink' => $linkRendererMethods,
			'\MediaWiki\Linker\LinkRenderer::makeKnownLink' => $linkRendererMethods,
			'\MediaWiki\Linker\LinkRenderer::makePreloadedLink' => $linkRendererMethods,
			'\MediaWiki\Linker\LinkRenderer::makeBrokenLink' => $linkRendererMethods,
			// The value of a status object can be pretty much anything, with any degree of taintedness
			// and escaping. Since it's a widely used class, it will accumulate a lot of links and taintedness
			// offset, resulting in huge objects (the short string representation of those Taintedness objects
			// can reach lengths in the order of tens of millions).
			// Since the plugin cannot keep track the taintedness of a property per-instance (as it assumes that
			// every property will be used with the same escaping level), we just annotate the methods as safe.
			'\StatusValue::newGood' => [
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Status::newGood' => [
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			'\StatusValue::getValue' => [
				'overall' => self::NO_TAINT
			],
			'\Status::getValue' => [
				'overall' => self::NO_TAINT
			],
			'\StatusValue::setResult' => [
				self::NO_TAINT,
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Status::setResult' => [
				self::NO_TAINT,
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
		];
	}

	/**
	 * Mark XSS's that happen in a Maintenance subclass as false a positive
	 *
	 * @inheritDoc
	 */
	public function isFalsePositive(
		int $combinedTaint,
		string &$msg,
		Context $context,
		CodeBase $code_base
	): bool {
		if ( $combinedTaint === self::HTML_TAINT ) {
			$path = str_replace( '\\', '/', $context->getFile() );
			if (
				strpos( $path, 'maintenance/' ) === 0 ||
				strpos( $path, '/maintenance/' ) !== false
			) {
				// For classes not using Maintenance subclasses
				$msg .= ' [Likely false positive because in maintenance subdirectory, thus probably CLI]';
				return true;
			}
			if ( !$context->isInClassScope() ) {
				return false;
			}
			$maintFQSEN = FullyQualifiedClassName::fromFullyQualifiedString(
				'\\Maintenance'
			);
			if ( !$code_base->hasClassWithFQSEN( $maintFQSEN ) ) {
				return false;
			}
			$classFQSEN = $context->getClassFQSEN();
			$isMaint = TaintednessVisitor::isSubclassOf( $classFQSEN, $maintFQSEN, $code_base );
			if ( $isMaint ) {
				$msg .= ' [Likely false positive because in a subclass of Maintenance, thus probably CLI]';
				return true;
			}
		}
		return false;
	}

	/**
	 * Disable double escape checking for messages with polymorphic methods
	 *
	 * A common cause of false positives for double escaping is that some
	 * methods take a string|Message, and this confuses the tool given
	 * the __toString() behaviour of Message. So disable double escape
	 * checking for that.
	 *
	 * This is quite hacky. Ideally the tool would treat methods taking
	 * multiple types as separate for each type, and also be able to
	 * reason out simple conditions of the form if ( $arg instanceof Message ).
	 * However that's much more complicated due to dependence on phan.
	 *
	 * @inheritDoc
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function modifyArgTaint(
		Taintedness $curArgTaintedness,
		Node $argument,
		int $argIndex,
		FunctionInterface $func,
		FunctionTaintedness $funcTaint,
		Context $context,
		CodeBase $code_base
	): Taintedness {
		if ( $curArgTaintedness->has( self::ESCAPED_TAINT ) ) {
			$argumentIsMaybeAMsg = false;
			/** @var \Phan\Language\Element\Clazz[] $classes */
			$classes = UnionTypeVisitor::unionTypeFromNode( $code_base, $context, $argument )
				->asClassList( $code_base, $context );
			try {
				foreach ( $classes as $cl ) {
					if ( $cl->getFQSEN()->__toString() === '\Message' ) {
						$argumentIsMaybeAMsg = true;
						break;
					}
				}
			} catch ( CodeBaseException $_ ) {
				// A class that doesn't exist, don't crash.
				return $curArgTaintedness;
			}

			$param = $func->getParameterForCaller( $argIndex );
			if ( !$argumentIsMaybeAMsg || !$param || !$param->getUnionType()->hasStringType() ) {
				return $curArgTaintedness;
			}
			/** @var \Phan\Language\Element\Clazz[] $classesParam */
			$classesParam = $param->getUnionType()->asClassList( $code_base, $context );
			try {
				foreach ( $classesParam as $cl ) {
					if ( $cl->getFQSEN()->__toString() === '\Message' ) {
						// So we are here. Input is a Message, and func expects either a Message or string
						// (or something else). So disable double escape check.
						return $curArgTaintedness->without( self::ESCAPED_TAINT );
					}
				}
			} catch ( CodeBaseException $_ ) {
				// A class that doesn't exist, don't crash.
				return $curArgTaintedness;
			}
		}
		return $curArgTaintedness;
	}
}

return new MediaWikiSecurityCheckPlugin;
