<?php namespace App\Http\Controllers;

use App\Http\Requests\GenerateSiteMapRequest;
use Input;

class SiteMapGeneratorController extends Controller
{
	public function generate(GenerateSiteMapRequest $request)
	{
		set_time_limit(600);

		$url = Input::get('url');

		$this->generator = \App::make('Generator')
			->setUrl($url)
			->setMaxDeepsLevel(Input::get('deeps_level'))
			->setPriorities(Input::all())
			->generate()
		;

		return redirect('/');
	}
}
