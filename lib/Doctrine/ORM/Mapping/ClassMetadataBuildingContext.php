<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Mapping\Factory\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\Factory\NamingStrategy;
use Doctrine\ORM\Reflection\ReflectionService;

/**
 * Class ClassMetadataBuildingContext
 *
 * @package Doctrine\ORM\Mapping\Factory
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ClassMetadataBuildingContext
{
    /** @var AbstractClassMetadataFactory */
    private $classMetadataFactory;

    /** @var ReflectionService */
    private $reflectionService;

    /** @var NamingStrategy */
    private $namingStrategy;

    /**
     * @var array<SecondPass>
     */
    protected $secondPassList = [];

    /**
     * @var bool
     */
    private $inSecondPass = false;

    /**
     * ClassMetadataBuildingContext constructor.
     *
     * @param AbstractClassMetadataFactory $classMetadataFactory
     * @param ReflectionService            $reflectionService
     * @param NamingStrategy|null          $namingStrategy
     */
    public function __construct(
        AbstractClassMetadataFactory $classMetadataFactory,
        ReflectionService $reflectionService,
        ?NamingStrategy $namingStrategy = null
    )
    {
        $this->classMetadataFactory = $classMetadataFactory;
        $this->reflectionService    = $reflectionService;
        $this->namingStrategy       = $namingStrategy ?: new DefaultNamingStrategy();
    }

    /**
     * @return AbstractClassMetadataFactory
     */
    public function getClassMetadataFactory() : AbstractClassMetadataFactory
    {
        return $this->classMetadataFactory;
    }

    /**
     * @return ReflectionService
     */
    public function getReflectionService() : ReflectionService
    {
        return $this->reflectionService;
    }

    /**
     * @return NamingStrategy
     */
    public function getNamingStrategy() : NamingStrategy
    {
        return $this->namingStrategy;
    }

    /**
     * @param SecondPass $secondPass
     *
     * @return void
     */
    public function addSecondPass(SecondPass $secondPass) : void
    {
        $this->secondPassList[] = $secondPass;
    }

    /**
     * @return bool
     */
    public function isInSecondPass() : bool
    {
        return $this->inSecondPass;
    }

    /**
     * @return void
     */
    public function validate() : void
    {
        $this->inSecondPass = true;

        foreach ($this->secondPassList as $secondPass) {
            /** @var SecondPass $secondPass */
            $secondPass->process($this);
        }

        $this->inSecondPass = false;
    }
}
