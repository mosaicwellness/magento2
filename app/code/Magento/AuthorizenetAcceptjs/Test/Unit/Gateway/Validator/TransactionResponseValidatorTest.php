<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AuthorizenetAcceptjs\Test\Unit\Gateway\Validator;

use Magento\AuthorizenetAcceptjs\Gateway\SubjectReader;
use Magento\AuthorizenetAcceptjs\Gateway\Validator\TransactionResponseValidator;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\AuthorizenetAcceptjs\Gateway\Validator\TransactionResponseValidator
 */
class TransactionResponseValidatorTest extends TestCase
{
    /**
     * @var ResultInterfaceFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var TransactionResponseValidator
     */
    private $validator;

    /**
     * @var ResultInterface
     */
    private $resultMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->resultFactoryMock = $this->createMock(ResultInterfaceFactory::class);
        $this->resultMock = $this->createMock(ResultInterface::class);

        $this->validator = $objectManagerHelper->getObject(
            TransactionResponseValidator::class,
            [
                'resultInterfaceFactory' => $this->resultFactoryMock,
                'subjectReader' => new SubjectReader(),
            ]
        );
    }

    /**
     * @param array $transactionResponse
     * @param bool $isValid
     * @param array $errorMessages
     * @dataProvider scenarioProvider
     *
     * @return void
     */
    public function testValidateScenarios(array $transactionResponse, bool $isValid, array $errorMessages)
    {
        $args = [];

        $this->resultFactoryMock->method('create')
            ->with($this->callback(function ($a) use (&$args) {
                // Spy on method call
                $args = $a;

                return true;
            }))
            ->willReturn($this->resultMock);

        $this->validator->validate([
            'response' => [
                'transactionResponse' => $transactionResponse,
            ]
        ]);

        $this->assertEquals($isValid, $args['isValid']);
        $this->assertEquals($errorMessages, $args['failsDescription']);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function scenarioProvider(): array
    {
        return [
            // This validator only cares about successful edge cases so test for default behavior
            [
                [
                    'responseCode' => '1',
                ],
                true,
                [],
            ],

            // Test for acceptable reason codes
            [
                [
                    'responseCode' => TransactionResponseValidator::RESPONSE_CODE_APPROVED,
                    'messages' => [
                        'message' => [
                            'code' => TransactionResponseValidator::RESPONSE_REASON_CODE_APPROVED,
                        ],
                    ],
                ],
                true,
                [],
            ],
            [
                [
                    'responseCode' => TransactionResponseValidator::RESPONSE_CODE_APPROVED,
                    'messages' => [
                        'message' => [
                            'code' => TransactionResponseValidator::RESPONSE_REASON_CODE_PENDING_REVIEW,
                        ],
                    ],
                ],
                true,
                [],
            ],
            [
                [
                    'responseCode' => TransactionResponseValidator::RESPONSE_CODE_APPROVED,
                    'messages' => [
                        'message' => [
                            'code' => TransactionResponseValidator::RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED,
                        ],
                    ],
                ],
                true,
                [],
            ],
            [
                [
                    'responseCode' => TransactionResponseValidator::RESPONSE_CODE_HELD,
                    'messages' => [
                        'message' => [
                            'code' => TransactionResponseValidator::RESPONSE_REASON_CODE_APPROVED,
                        ],
                    ],
                ],
                true,
                [],
            ],
            [
                [
                    'responseCode' => TransactionResponseValidator::RESPONSE_CODE_HELD,
                    'messages' => [
                        'message' => [
                            'code' => TransactionResponseValidator::RESPONSE_REASON_CODE_PENDING_REVIEW,
                        ],
                    ],
                ],
                true,
                [],
            ],
            [
                [
                    'responseCode' => TransactionResponseValidator::RESPONSE_CODE_HELD,
                    'messages' => [
                        'message' => [
                            'code' => TransactionResponseValidator::RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED,
                        ],
                    ],
                ],
                true,
                [],
            ],

            // Test for reason codes that aren't acceptable
            [
                [
                    'responseCode' => TransactionResponseValidator::RESPONSE_CODE_APPROVED,
                    'messages' => [
                        'message' => [
                            [
                                'description' => 'bar',
                                'code' => 'foo',
                            ],
                        ],
                    ],
                ],
                false,
                ['foo'],
            ],
            [
                [
                    'responseCode' => TransactionResponseValidator::RESPONSE_CODE_APPROVED,
                    'messages' => [
                        'message' => [
                            // Alternate, non-array sytax
                            'text' => 'bar',
                            'code' => 'foo',
                        ],
                    ],
                ],
                false,
                ['foo'],
            ],
        ];
    }
}
