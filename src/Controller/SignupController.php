<?php


namespace App\Controller;

use App\Controller;
use App\Entity\User;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Transaction;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\UrlGeneratorInterface;

final class SignupController extends Controller
{
    protected function getId(): string
    {
        return 'signup';
    }

    public function signup(RequestInterface $request, IdentityRepositoryInterface $identityRepository, ORMInterface $orm, UrlGeneratorInterface $urlGenerator, LoggerInterface $logger): ResponseInterface
    {
        $body = $request->getParsedBody();
        $error = null;

        if ($request->getMethod() === Method::POST) {
            try {
                foreach (['login', 'password'] as $name) {
                    if (empty($body[$name])) {
                        throw new \InvalidArgumentException(ucfirst($name) . ' is required.');
                    }
                }

                /** @var \App\Entity\User $identity */
                $identity = $identityRepository->findByLogin($body['login']);
                if ($identity !== null) {
                    throw new \InvalidArgumentException('Unable to register user with such username.');
                }

                $user = new User($body['login'], $body['password']);

                $transaction = new Transaction($orm);
                $transaction->persist($user);

                $transaction->run();
                return $this->responseFactory
                    ->createResponse(302)
                    ->withHeader(
                        'Location',
                        $urlGenerator->generate('site/index')
                    );
            } catch (\Throwable $e) {
                $logger->error($e);
                $error = $e->getMessage();
            }
        }

        return $this->render(
            'signup',
            [
                'body' => $body,
                'error' => $error,
                'csrf' => $request->getAttribute('csrf_token'),
            ]
        );
    }
}
