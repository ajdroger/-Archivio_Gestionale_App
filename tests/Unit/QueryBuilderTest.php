<?php

use MCAG\InfrastrutturaIT\Persistence\QueryBuilder;

test('select basic query', function () {
    $qb = new QueryBuilder();
    $sql = $qb->select('*')->from('users')->toSql();

    expect($sql)->toBe('SELECT * FROM users');
});

test('select with where clause', function () {
    $qb = new QueryBuilder();
    $sql = $qb->select(['id', 'name'])
        ->from('users')
        ->where('email', '=', 'test@example.com')
        ->toSql();

    expect($sql)->toBe('SELECT id, name FROM users WHERE email = ?');
    expect($qb->getBindings())->toBe(['test@example.com']);
});

test('pagination adds limit and offset', function () {
    $qb = new QueryBuilder();
    $sql = $qb->select('*')
        ->from('soci')
        ->pagination(2, 50) // Page 2, 50 per page -> offset 50
        ->toSql();

    expect($sql)->toContain('LIMIT 50');
    expect($sql)->toContain('OFFSET 50');
});

test('complex query with joins and ordering', function () {
    $qb = new QueryBuilder();
    $sql = $qb->select('u.*')
        ->from('users')
        ->join('profiles', 'users.id = profiles.user_id')
        ->orderBy('created_at', 'DESC')
        ->toSql();

    expect($sql)->toBe('SELECT u.* FROM users INNER JOIN profiles ON users.id = profiles.user_id ORDER BY created_at DESC');
});
