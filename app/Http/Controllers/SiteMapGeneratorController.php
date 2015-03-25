<?php namespace App\Http\Controllers;

use App\Http\Requests\GenerateSiteMapRequest;
use Input;

class SiteMapGeneratorController extends Controller
{
	public function generate(GenerateSiteMapRequest $request)
	{
		$url = Input::get('url');
		$this->generator = \App::make('Generator')
			->setUrl($url)
			->generate()
		;

		return redirect('/');
	}
}
