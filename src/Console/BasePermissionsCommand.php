<?php

namespace Winavin\Permissions\Console;

use Illuminate\Console\Command;
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
            return 'App'; // fallback to default if path isn't within app/
        }

        $relative = trim( str_replace( $appPath, '', $inputPath ), '/' );
        return $relative ? 'App\\' . str_replace( '/', '\\', $relative ) : 'App';
    }


    protected function publishStub( string $stubPath, string $targetPath ) : void
    {
        if( !File::exists( $stubPath ) ) {
            $this->error( "Stub file not found: $stubPath" );
            return;
        }

        $namespace = $this->namespaceFromPath( dirname( $targetPath ) );
        $content   = File::get( $stubPath );

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
