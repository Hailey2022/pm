# thinkphp5 常用的一些扩展类库

> 更新完善中

> 以下类库都在`\\think\\helper`命名空间下

## Str
> 字符串操作

```

Str::contains($haystack, $needles)


Str::endsWith($haystack, $needles)


Str::random($length = 16)


Str::lower($value)


Str::upper($value)


Str::length($value)


Str::substr($string, $start, $length = null)

```

## Hash
> 创建密码的哈希

```

Hash::make($value, $type = null, array $options = [])


Hash::check($value, $hashedValue, $type = null, array $options = [])

```

## Time
> 时间戳操作

```

Time::today();


Time::yesterday();


Time::week();


Time::lastWeek();


Time::month();


Time::lastMonth();


Time::year();


Time::lastYear();


Time::dayToNow(7)


Time::dayToNow(7, true)


Time::daysAgo(7)


Time::daysAfter(7)


Time::daysToSecond(5)


Time::weekToSecond(5)

```