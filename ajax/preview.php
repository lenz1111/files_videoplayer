<?php
/**
 * @author Piotr Filiciak <piotr@filiciak.pl>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

// no php execution timeout for webdav
set_time_limit(0);

// Turn off output buffering to prevent memory problems
\OC_Util::obEnd();

$serverFactory = new \OCA\DAV\Connector\Sabre\ServerFactory(
	\OC::$server->getConfig(),
	\OC::$server->getLogger(),
	\OC::$server->getDatabaseConnection(),
	\OC::$server->getUserSession(),
	\OC::$server->getMountManager(),
	\OC::$server->getTagManager(),
	\OC::$server->getRequest()
);

// Backends
$authBackend = new \OCA\DAV\Connector\Sabre\Auth(
	\OC::$server->getSession(),
	\OC::$server->getUserSession(),
	\OC::$server->getRequest(),
	'principals/'
);

$path = $_GET["path"];
$view = \OC\Files\Filesystem::getView();
if (is_null($view)) exit;

if (!$view->isReadable($path)) {
	header("HTTP/1.0 404 Not Found");
	$tmpl = new OCP\Template( '', '404', 'guest' );
	$tmpl->assign('file', $path);
	$tmpl->printPage();
	exit;
}

if (\OC::$server->getConfig()->getSystemValue('enable_movie_transcode', false)) {

	$user = \OC_User::getUser();
	$id = $view->getFileInfo($path)->getId();
	$viewThumb = new \OC\Files\View('/'.$user.'/'.OC\Preview::getThumbnailsFolder().'/'.$id);

	if ($viewThumb->isReadable(OC\Preview::getMovieThumbnailFilename())) {
		$path = OC\Preview::getMovieThumbnailFilename();
		$view = $viewThumb;
	}
}

$server = $serverFactory->createServer('', $path, $authBackend, function() use ($view) {
	return $view;
});

// And off we go!
$server->exec();
