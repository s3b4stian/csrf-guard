<?php

/**
 * Linna Array.
 *
 * @author Sebastian Rapetti <sebastian.rapetti@alice.it>
 * @copyright (c) 2017, Sebastian Rapetti
 * @license http://opensource.org/licenses/MIT MIT License
 */
declare(strict_types=1);

use Linna\CsrfGuard;
use PHPUnit\Framework\TestCase;

/**
 * Cross-site Request Forgery Guard Test.
 */
class CsrfGuardTest extends TestCase
{
    /**
     * Test new instance.
     * 
     * @runInSeparateProcess
     */
    public function testNewInstance()
    {
        session_start();
        
        $this->assertInstanceOf(CsrfGuard::class, (new CsrfGuard(64, 16)));
    }
    
    /**
     * Test new instance before session start.
     * 
     * @expectedException RuntimeException
     */
    public function testNewInstanceBeforeSessionStart()
    {
        $this->assertInstanceOf(CsrfGuard::class, (new CsrfGuard(64, 16)));
    }
    
    /**
     * Contructor wrong arguments provider.
     *
     * @return array
     */
    public function contructorWrongArgumentsProvider() : array
    {
        return [
            ['64','16'],
            [true, false],
            [64.64, 16.16],
            [function () {
            },function () {
            }],
            [(object) ['name' => 'foo'], (object) ['name' => 'bar']],
            [[64], [16]],
        ];
    }
    
    /**
     * Test new instance with wrong arguments.
     *
     * @dataProvider contructorWrongArgumentsProvider
     * @expectedException TypeError
     */
    public function testNewInstanceWithWrongArguments($maxStorage, $tokenStrength)
    {
        (new CsrfGuard($maxStorage, $tokenStrength));
    }
    
    /**
     * Size limit provider.
     *
     * @return array
     */
    public function sizeLimitProvider() : array
    {
        return [[2], [4], [8], [16], [32], [64], [128], [3], [5], [9], [17], [33], [65], [129]];
    }
    
    /**
     * Test token limit.
     *
     * @dataProvider sizeLimitProvider
     * @runInSeparateProcess
     */
    public function testDequeue(int $sizeLimit)
    {
        session_start();
        
        $csrf = new CsrfGuard($sizeLimit, 16);
        
        for ($i = 0; $i < $sizeLimit + 1; $i++) {
            $token = $csrf->getToken();
        }
        
        session_commit();
        session_start();
        
        $csrf = new CsrfGuard($sizeLimit, 16);
        
        $this->assertEquals($sizeLimit, count($_SESSION['CSRF']));
        
        session_destroy();
    }
    
    /**
     * Test get token.
     *
     * @runInSeparateProcess
     */
    public function testGetToken()
    {
        session_start();
        
        $csrf = new CsrfGuard(32, 16);
        
        $token = $csrf->getToken();
        
        $key = key($_SESSION['CSRF']);
        
        $this->assertEquals($key, $token['name']);
        $this->assertEquals($_SESSION['CSRF'][$key], $token['token']);
        
        session_destroy();
    }
    
    /**
     * Test get hidden input.
     *
     * @runInSeparateProcess
     */
    public function testGetHiddenInput()
    {
        session_start();
        
        $csrf = new CsrfGuard(32, 16);
        
        $input = $csrf->getHiddenInput();
        
        $key = key($_SESSION['CSRF']);
        $token = $_SESSION['CSRF'][$key];
        
        $this->assertEquals('<input type="hidden" name="'.$key.'" value="'.$token.'" />', $input);
        
        session_destroy();
    }
    
    /**
     * Test validate.
     *
     * @runInSeparateProcess
     */
    public function testValidate()
    {
        session_start();
        
        $csrf = new CsrfGuard(32, 16);
        $csrf->getToken();
        
        $key = key($_SESSION['CSRF']);
        $token = $_SESSION['CSRF'][$key];
        
        $this->assertEquals(true, $csrf->validate([$key => $token]));
        $this->assertEquals(false, $csrf->validate(['foo' => $token]));
        $this->assertEquals(false, $csrf->validate([$key => 'foo']));
        
        session_destroy();
    }
}