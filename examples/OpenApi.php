<?php
namespace examples;

use net\razshare\catpaw\attributes\http\methods\GET;
use net\razshare\catpaw\attributes\http\Path;
use net\razshare\catpaw\attributes\Produces;
use stdClass;

#[Path("/openapi")]
class OpenApi {

	#[GET]
	#[Produces("text/plain")]
	public function json(){
		return print_r([
			
			
				"title" => "Sample Pet Store App",
				"description" => "This is a sample server for a pet store.",
				"termsOfService" => "http://example.com/terms/",
				"contact" => [
				  "name" => "API Support",
				  "url" => "http://www.example.com/support",
				  "email" => "support@example.com"
				],
				"license" => [
				  "name" => "Apache 2.0",
				  "url" => "https://www.apache.org/licenses/LICENSE-2.0.html"
				],
				"version" => "1.0.1",

				"servers" => [
					[
						"url" => "https://development.gigantic-server.com/v1",
						"description" => "some server description",
						"variables" => [
							"username" => [
								"default" => "my-cool-username",
								"description" => "variable description"
							],
							"port" => [
								"enum" => [
									"443",
									"80",
									"8080"
								],
								"default" => "8080"
							],
							"basePath" => [
								"default" => "v1"
							]
						]
					]
				],

				"components" => [
					"schemas" => [
						"Task" => [
							"type" => "object",
							"properties" => [
								"id" => [ "type" => "integer" ],
								"title" => [ "type" => "string" ],
								"description" => [ "type" => "string" ],
								"created" => [ "type" => "string" ],
								"updated" => [ "type" => "string" ],
							]
						]
					]
				]
			


		],true);
	}
}