<?php

/**
 * @author Adriaan Knapen <a.d.knapen@protonmail.com>
 * @date 1-3-2017
 */

/**
 * Class Consumption
 * @property CI_DB_query_builder $db
 */
class Consumption extends ModelFrame
{
    const FIELD_AMOUNT = 'amount';

    const CONSUMPTION_DEFAULT_AMOUNT = 0;

    private function add($loginId) {
        return $this->db
            ->insert(self::name(), [
                Login::FIELD_LOGIN_ID => $loginId,
            ]);
    }

    public function get($loginId) {
        $result = $this->db
            ->where([Login::FIELD_LOGIN_ID => $loginId])
            ->get(self::name())
            ->row_array()[self::FIELD_AMOUNT];

        // Check if the user was present.
        if ($result === null) {
            $this->add($loginId);

            return $this->get($loginId);
        } else {
            return $result;
        }
    }

    public function change($loginId, $delta) {
        $this->db->trans_start();
            $oldAmount = $this->get($loginId);

            // Check if the tuple even exists.
            if ($oldAmount === null) {
                $newAmount = self::CONSUMPTION_DEFAULT_AMOUNT + $delta;
            } else {
                $newAmount = $oldAmount + $delta;
            }

            $success = $this->set($loginId, $newAmount);
        $this->db->trans_complete();

        return $this->db->trans_status() && $success;
    }

    /**
     * Sets the amount for a login id. NOTE: Creates a new tuple if the login id didn't have one already.
     *
     * @param $loginId
     * @param $amount
     * @return bool
     */
    private function set($loginId, $amount) {
        return $this->db
            ->replace(
                self::name(),
                [
                    self::FIELD_AMOUNT => $amount,
                    Login::FIELD_LOGIN_ID => $loginId
                ]
            );
    }

    public function v1() {
        return [
            'requires' => [
                Login::class => 1,
            ],
            'add' => [
                Login::FIELD_LOGIN_ID => [
                    'type' => 'primary|foreign',
                    'table' => Login::name(),
                    'field' => Login::FIELD_LOGIN_ID,
                    'unique' => true,
                ],
                self::FIELD_AMOUNT => [
                    'type' => 'INT',
                    'constraint' => 9,
                    'default' => self::CONSUMPTION_DEFAULT_AMOUNT,
                ],
            ],
        ];
    }
}