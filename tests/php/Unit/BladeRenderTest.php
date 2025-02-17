<?php

declare(strict_types = 1);

namespace AvtoDev\Back2Front\Tests\Unit;

use AvtoDev\Back2Front\Back2FrontInterface;
use AvtoDev\Back2Front\Tests\AbstractTestCase;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * @covers \AvtoDev\Back2Front\ServiceProvider<extended>
 */
class BladeRenderTest extends AbstractTestCase
{
    /**
     * @return void
     */
    public function testRendering(): void
    {
        /** @var Back2FrontInterface $service */
        $service = $this->app->make(Back2FrontInterface::class);
        /** @var ViewFactory $view */
        $view = $this->app->make(ViewFactory::class);
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);

        $view->addNamespace('stubs', __DIR__ . '/../stubs/view');

        $data = [
            'foo' => 'bar',
            'baz' => 123,
            321   => 'asd',
        ];

        foreach ($data as $key => $value) {
            $service->put($key, $value);
        }

        $rendered = $view->make('stubs::view')->render();

        $this->assertMatchesRegularExpression("~window,\s?['\"]{$config->get('back-to-front.stack_name')}['\"],~", $rendered);

        foreach ($data as $key => $value) {
            $this->assertStringContainsString((string) $key, $rendered);
            $this->assertStringContainsString((string) $value, $rendered);
        }
    }

    /**
     * @return void
     */
    public function testRenderCaching(): void
    {
        /** @var Back2FrontInterface $service */
        $service = $this->app->make(Back2FrontInterface::class);
        /** @var ViewFactory $view */
        $view = $this->app->make(ViewFactory::class);

        $view->addNamespace('stubs', __DIR__ . '/../stubs/view');

        // Set first state
        $service->put('foo', 'bar');

        $rendered = $view->make('stubs::view')->render();

        $this->assertStringContainsString('foo', $rendered);

        // Set another state
        $service->put('test_key', 'bar2');
        $service->forget('foo');

        $rendered2 = $view->make('stubs::view')->render();

        // See actual data
        $this->assertStringNotContainsString('foo', $rendered2);
        $this->assertStringContainsString('test_key', $rendered2);
    }
}
