<?php

namespace App\Libraries\Packages;

use App\Models\TokenIssueModel;
use App\Models\TokenWhitelistModel;

use App\Libraries\Logger\Logger;

class TokenIssuer
{
    private $request;
    private $tokenIssueModel;
    private $issue;

    public function __construct()
    {
        $this->request = service('request');
        $this->tokenIssueModel = new TokenIssueModel();
    }

    public function issueChange($clientId)
    {
        $this->tokenIssueModel->create($clientId);
    }

    public function checkToken()
    {
        $this->getNewTokenIssueId();

        if (!$this->issue) {
            return false;
        }

        if (!$this->issue->old_token_id) {
            $this->saveOldTokenId();
            return false;
        }

        if ($this->oldTokenUsed()) {
            return $this->manageOldTokenUse();
        }

        if ($this->newTokenUsed()) {
            return $this->manageNewTokenUse();
        }
    }

    

    private function saveOldTokenId()
    {
        $this->issue->old_token_id = $this->request->decodedJwt->tokenId;
        $this->tokenIssueModel->save($this->issue);
    }

    private function getNewTokenIssueId()
    {
        $this->issue = $this->tokenIssueModel->hasNewTokenIssued($this->request->decodedJwt->clientId);
    }

    private function oldTokenUsed()
    {
        return $this->issue->old_token_id == $this->request->decodedJwt->tokenId;
    }

    private function manageOldTokenUse()
    {
        if ($this->issue->old_token_uses > 6){
            $this->cancelNewToken();
            $this->issueFailed();
            return false;
        }
        $this->issue->old_token_uses++;
        $this->tokenIssueModel->save($this->issue);
        return $this->issue->new_token;
    }

    private function newTokenUsed()
    {
        return $this->issue->new_token_id == $this->request->decodedJwt->tokenId;
    }

    private function manageNewTokenUse()
    {
        $this->cancelOldToken();
        $this->issueDone();
        return false;
    }

    private function cancelNewToken()
    {
        $tokenWhitelistModel = new TokenWhitelistModel();
        $tokenWhitelistModel->remove($this->issue->new_token_id);
    }

    private function issueFailed()
    {
        $this->issue->status = 'failed';
        $this->tokenIssueModel->save($this->issue);
    }

    private function cancelOldToken()
    {
        $tokenWhitelistModel = new TokenWhitelistModel();
        $tokenWhitelistModel->remove($this->issue->old_token_id);
    }

    private function issueDone()
    {
        $this->issue->status = 'done';
        $this->tokenIssueModel->save($this->issue);
    }
}
