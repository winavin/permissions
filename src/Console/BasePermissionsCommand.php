<?php

namespace Winavin\Permissions\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class BasePermissionsCommand extends Command
{
    protected string  $prefix;
    protected ?string $path;

    public function handle() : void
    {
        $this->prefix = Str::studly( $this->argument( 'name' ) );
        $this->path   = $this->option( 'path' ) ?? app_path();
        $this->process();
    }

    abstract protected function process() : void;

    protected function prefix() : string
    {
        return $this->prefix;
    }

    protected function prefixSnake() : string
    {
        return Str::snake( $this->prefix );
    }

    protected function namespaceFromPath( string $path ) : string
    {
        $appPath   = str_replace( '\\', '/', app_path() );         // Normalize slashes
        $inputPath = str_replace( '\\', '/', $path );              // Normalize input path

        if( !str_starts_with( $inputPath, $appPath ) ) {
            return 'App';
        }

        $relative = trim( str_replace( $appPath, '', $inputPath ), '/' );

        return $relative ? 'App\\' . str_replace( '/', '\\', $relative ) : 'App';
    }


    protected function publishStub( string $stubPath, string $targetPath ) : void
    {

        $namespace = $this->namespaceFromPath( dirname( $targetPath ) );

        // Skip if file already exists and --force not used
        if (File::exists($targetPath) && !$this->option('force')) {
            $this->warn("⚠ Skipped (exists): $targetPath. Use --force to overwrite.");
            return;
        }

        try {

            $content = File::get( $stubPath );

        } catch( FileNotFoundException $e ) {

            $this->error( "Stub file not found: $stubPath" );
            return;

        }

        $content = str_replace(
            [ '{{Prefix}}', '{{prefix}}', '{{namespace}}' ],
            [ $this->prefix(), $this->prefixSnake(), $namespace ],
            $content
        );

        File::ensureDirectoryExists( dirname( $targetPath ) );
        File::put( $targetPath, $content );
        $this->info( "✔ Published: $targetPath" );
    }
}
