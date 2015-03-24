<?php namespace App\Http\Controllers;

use App\Http\Requests\GenerateSiteMapRequest;
use Input;

class SiteMapGeneratorController extends Controller
{
	public function generate(GenerateSiteMapRequest $request)
	{
		$url = Input::get('url');

		return redirect('/');
	}
}
