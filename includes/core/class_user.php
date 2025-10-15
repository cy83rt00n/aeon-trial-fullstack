<?php

class User {

    // GENERAL

    public static function user_info($d) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, phone, access FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'access' => (int) $row['access']
            ];
        } else {
            return [
                'id' => 0,
                'access' => 0
            ];
        }
    }


    private static function user_create($data)
    {
        try {
            $data['phone'] = preg_replace('~[^0-9]~', '', $data['phone']);
            $data['email'] = strtolower($data['email']);
            $data['plots'] = preg_replace('~[^0-9,]~', '', $data['plots']);

            $query = "INSERT INTO users (first_name, last_name, phone, email, plot_id) " .
                "VALUES (?,?,?,?,?)";

            $stmnt = DB::connect()->prepare($query);
            $stmnt->bindValue(1, $data['first_name']);
            $stmnt->bindValue(2, $data['last_name']);
            $stmnt->bindValue(3, phone_formatting($data['phone']));
            $stmnt->bindValue(4, $data['email']);
            $stmnt->bindValue(5, $data['plots']);
            $stmnt->execute();
        } catch (PDOException $ex) {
            die(json_encode(["error" => $ex->errorInfo]));
        } catch (Exception $ex) {
            die(json_encode(["error" => $ex->getMessage()]));
        }
    }

    private static function user_read($user_id)
    {
        try {
            $query = "SELECT user_id, first_name, last_name, phone, email, access, plot_id " .
                "FROM users " .
                "WHERE user_id=? " .
                "LIMIT 1";
            $stmnt = DB::connect()->prepare($query);
            $stmnt->bindValue(1, $user_id);
            $stmnt->execute();

            if ($row = $stmnt->fetch()) {
                return [
                    'id' => (int) $row['user_id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'plots' => $row['plot_id'],
                    'access' => (int) $row['access']
                ];
            } else {
                return [
                    'id' => 0,
                    'first_name' => '',
                    'last_name' => '',
                    'phone' => '',
                    'email' => '',
                    'plots' => '',
                    'access' => 0
                ];
            }
        } catch (PDOException $ex) {
            die(json_encode(["error" => $ex->errorInfo]));
        } catch (Exception $ex) {
            die(json_encode(["error" => $ex->getMessage()]));
        }
    }

    private static function user_update($data)
    {
        try {
            $data['phone'] = preg_replace('~[^0-9]~', '', $data['phone']);
            $data['email'] = strtolower($data['email']);
            $data['plots'] = preg_replace('~[^0-9,]~', '', $data['plots']);

            $query = "UPDATE users " .
                "SET first_name=?, last_name=?, phone=?, email=?, plot_id=? " .
                "WHERE user_id=?";
            $stmnt = DB::connect()->prepare($query);
            $stmnt->bindValue(1, $data['first_name']);
            $stmnt->bindValue(2, $data['last_name']);
            $stmnt->bindValue(3, $data['phone']);
            $stmnt->bindValue(4, $data['email']);
            $stmnt->bindValue(5, $data['plots']);
            $stmnt->bindValue(6, $data['user_id']);
            $stmnt->execute();
        } catch (PDOException $ex) {
            die(json_encode(["error"=>$ex->errorInfo]));
        } catch (Exception $ex) {
            die(json_encode(["error"=>$ex->getMessage()]));
        }
    }

    private static function user_delete ($user_id) {
        $query = "DELETE FROM users WHERE user_id=?";
        $stmnt = DB::connect()->prepare($query);
        $stmnt->bindValue(1,$user_id, PDO::PARAM_INT);
        $stmnt->execute();
    }
    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }

    public static function users_list($data = [])
    {
        try {
            $search = isset($data['search']) && trim($data['search']) ? trim($data['search']) : '';
            $offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
            $limit = 20;
            $items = [];
            $where = null;

            if ($search) {
                $where[] = "first_name LIKE :search";
                $where[] = "last_name LIKE :search";
                $where[] = "CONCAT(first_name, ' ', last_name) LIKE :search";
                $where[] = "phone LIKE :search";
                $where[] = "email LIKE :search";
            }

            $where = $where ? "WHERE " . implode(" OR ", $where) : "";
            $query = "SELECT * FROM users " . $where . " LIMIT :offset,:limit";
            $stmnt = DB::connect()->prepare($query);
            if ($where) $stmnt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
            $stmnt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmnt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmnt->execute();
            while ($row = $stmnt->fetch()) {
                $items[] = [
                    'id' => $row['user_id'],
                    'plot_id' => $row['plot_id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'phone' => phone_formatting($row['phone']),
                    'email' => $row['email'],
                    'last_login' => ($row['last_login']) ? date('Y/m/d', $row['last_login']) : 0
                ];
            }

            $query = "SELECT count(*) as users_count FROM users " . $where;
            $stmnt = DB::connect()->prepare($query);
            if ($where) $stmnt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
            $stmnt->execute();
            $count = ($row = $stmnt->fetch()) ? $row['users_count'] : 0;
            $url = 'users?';
            paginator($count, $offset, $limit, $url, $paginator);
            // output
            return ['items' => $items, 'paginator' => $paginator];
        } catch (PDOException $ex) {
            die(json_encode(["error" => $ex->errorInfo]));
        } catch (Exception $ex) {
            die(json_encode(["error" => $ex->getMessage()]));
        }
    }
    public static function edit_window_read($data) {
        $user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
        HTML::assign('user', self::user_read($user_id));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }

    public static function edit_window_update($data)
    {
        $user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
        ($user_id < 1) ? self::user_create($data) : self::user_update($data);
        $users_list = self::users_list($data);
        HTML::assign('users', $users_list['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $users_list['paginator']];
    }

    public static function edit_window_delete($data) {
        $user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
        if($user_id < 1) throw new Exception('Error: user id can\'t be less than 1' );
        self::user_delete($user_id);
        $users_list = self::users_list($data);
        HTML::assign('users', $users_list['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $users_list['paginator']];
    }
}
