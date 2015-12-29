# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Fixed
  - [Fix the write-sql option](https://github.com/doctrine/migrations/pull/400)
### Changed
  - [Dropped the support for php 5.4](https://github.com/doctrine/migrations/pull/393)
  - [Again make the phar more than 3 times smaller](https://github.com/doctrine/migrations/pull/396)
### Added
  - [Give the possibility to override the Migration template](https://github.com/doctrine/migrations/pull/391)

## [1.2.1] - 2015-12-23
### Fixed
  - [Fix the write-sql option](https://github.com/doctrine/migrations/pull/400)

## [1.2.0] - 2015-12-15
### Fixed
  - [fix "all migrated versions shown as unavailable executed" ](https://github.com/doctrine/migrations/commit/875849e2a80d37dc8bf5dd0663e464b6789e3b56)
  - [Prevent the use of 0 as migration name as it is used internally and conflict](http://github.com/doctrine/migrations/commit/5df49c5ad5dc2265401a54a3b9e6ecb3e7cda8d0)
  - [composer: drop symfony/console from suggested packages, since it's required package](http://github.com/doctrine/migrations/commit/d263c7bfac7188009ab0717ed5aa6577e80
  - [fix spaces and complete missing file-docblocks](http://github.com/doctrine/migrations/commit/4b68a69c3e35492b36ec140ebb216cdb80ffe655)
  - [RecursiveDirectoryIterator don't obtain some order of the file list.](http://github.com/doctrine/migrations/commit/ed95c05c14381e64404f1135763fcc9b65317b96)
  - [Fix the yml parser issue with unescaped backslash in double quoted string](http://github.com/doctrine/migrations/commit/af3cce7d2e490ead821fcbdb54b4772b4913ee1d)

### Changed
  - [Adding compression to the generation of the phar](http://github.com/doctrine/migrations/commit/70730ff8655f0be89ce0f06d1e279930d7eb2550)
  - [tests: move autoload of tests to composer](http://github.com/doctrine/migrations/commit/3a4f8368e4b7b95d2e6c51c26f6dc41bb05a5ce5)
  - [travis: drop PHP 7.0 from allowed failures, it passes well](http://github.com/doctrine/migrations/commit/57ec2f071a7a840c554058b77f2089893d06ba23)
  - [Revert the use of exec to run sql queries](http://github.com/doctrine/migrations/commit/0af6e6e705b905a56cbed26cb5c17faad4c2c04f)

### Added
  - [Adding a little script to prepare and generate the phar](http://github.com/doctrine/migrations/commit/3a8ef413e7f8a42d4e0f3d32d30601b26fb27e60)
  - [Configuration: check if migration class exists added](http://github.com/doctrine/migrations/commit/a53d7c83b319341c985d2a21950e260fa55b0b8d)
  - [Made composer.json compatible with SF 3.0](http://github.com/doctrine/migrations/commit/4e909f2e661a8414a3e04ce987a09c9e849cd13f)
  - [Added configurable version column name](http://github.com/doctrine/migrations/commit/02ddf4318b84a20bb0e3486acfc6e6f41cc63426)

## [1.1.0] - 2015-09-29
### Fixed
  - [Switch unexisting <warning> tag to <comment>](http://github.com/doctrine/migrations/commit/93a81ff0dcc858de4df5c17d97f2f24b3bfa3c36)
  - [Ensure that Yaml::parse return an array as it can apparently return a string](http://github.com/doctrine/migrations/commit/1499f0cc3e5f5b20a510ba8f0779d5c68a9e5084)
  - [Avoid uploading code coverage to Scrutinizer for HHVM and PHP 7](http://github.com/doctrine/migrations/commit/d47d65021dcb711480bf27f6d0bbba138e220f12)
  - [Improve the Travis configuration by persisting the composer cache](http://github.com/doctrine/migrations/commit/bda0509b479ae6605b8fa749b0999b4ce2ff8c04)
  - [Keep the license file in the downloadable archives]
### Changed
  - [Use short array syntax](http://github.com/doctrine/migrations/commit/50a6e18c95ff617325229a4a649d65c1a11445bc)
  - [composer: use PSR-4 autoload](http://github.com/doctrine/migrations/commit/7fb8d301b2f4d4a564433165e0604b7d34013844)
  - [refactoring the configuration loading](http://github.com/doctrine/migrations/commit/e95b277111c74cfe65eb959d4471f45a815e1ece)
  - [Drop support for php 5.3]
  - [compressing the phar so that it's half the size now]
### Added
  - [Added the ability to auto create migrations in a folder by month and year](http://github.com/doctrine/migrations/commit/0b8e40868e12a36de7f689add61857b9ba29c4bc)
  - [Set default name for configuration helper](http://github.com/doctrine/migrations/commit/1f3592f2f126a022db275192f17b8d5c01f19822)
  - [Added ability to load config from php array or json files.](http://github.com/doctrine/migrations/commit/8cf01d623f9eb3728ba86c22970347107a8f0be7)
  - [Added the possibility to inject the OutputWriter after instantiation]
  - [Added the --allow-no-migration option to avoid throwing an exception if no migrations are found]
