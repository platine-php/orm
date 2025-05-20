## PLATINE ORM
**Platine ORM provides a flexible and powerful ORM implementing a data-mapper pattern.**

[![Project Status](http://opensource.box.com/badges/active.svg)](http://opensource.box.com/badges)
[![Latest Stable Version](https://poser.pugx.org/platine-php/orm/v)](https://packagist.org/packages/platine-php/orm)
[![Latest Unstable Version](https://poser.pugx.org/platine-php/orm/v/unstable)](https://packagist.org/packages/platine-php/orm)
[![Total Downloads](https://poser.pugx.org/platine-php/orm/downloads)](https://packagist.org/packages/platine-php/orm)
[![License](https://poser.pugx.org/platine-php/orm/license)](https://packagist.org/packages/platine-php/orm)  
![Tests](https://github.com/platine-php/orm/actions/workflows/ci.yml/badge.svg)
[![Quality Score](https://img.shields.io/scrutinizer/g/platine-php/orm.svg?style=flat-square)](https://scrutinizer-ci.com/g/platine-php/orm)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/852e352455644c8abd8209f2036eaab2)](https://app.codacy.com/gh/platine-php/orm/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Codacy Badge](https://app.codacy.com/project/badge/Coverage/852e352455644c8abd8209f2036eaab2)](https://app.codacy.com/gh/platine-php/orm/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_coverage)

### Requirements 
- **PHP >= 8.0** 

### Installation
#### Using composer (recommended)
```bash
composer require platine-php/orm
```

### License
MIT License See [LICENSE.MD](LICENSE.MD)

### Resources
- [Documentation](https://docs.platine-php.com/packages/orm)

### Change make to opis/orm version 1.0.x-dev
**Platine ORM** get inspiration from **opis/orm**. 
All notable changes to [opis/orm](https://github.com/opis/orm) are documented below.

#### Global changes

- Update code to PHP 7.4 (typed properties, **object** type, etc.)
- Add **declare(strict_types=1);** in all files
- Change all namespace **Opis\ORM** to **Platine\Orm**
- Add interface **RepositoryInterface** and class **Repository**
- Move classes **IEntityMapper, IDataMapper, EntityMapper, DataMapper, Proxy** to **Platine\Orm\Mapper**
- Move classes **Query, EntityQuery** to **Platine\Orm\Query**
- Rename interface **IEntityMapper** to **EntityMapperInterface**
- Rename interface **IDataMapper** to **DataMapperInterface**
- Move method **mapEntity** of interface **IMappableEntity** to **Entity** class and set as abstract method to force each child class to implements it
- Remove interface **IMappableEntity**
- Move classes **ForeignKey, PrimaryKey, Junction, Relation, RelationFactory, LazyLoader, BelongsTo, HasOneOrMany, ShareOneOrMany** to **Platine\Orm\Relation**
- Rename class **LazyLoader** to **RelationLoader**
- Add new abstract class **ShareRelation** in **Platine\Orm\Relation** to containt the implemention of **ShareOneOrMany**
- Add new abstract class **HasRelation** in **Platine\Orm\Relation** to containt the implemention of **HasOneOrMany**
- Extract class **ShareOneOrMany** into two classes **ShareOne** and **ShareMany**
- Extract class **HasOneOrMany** into two classes **HasOne** and **HasMany**
- Add Exceptions packages under namespace **Platine\Database\Exception**
- Change all typehint array to array notation based on static analysis tools (phpstan), example array<int, string>, etc.
- In each class reorder all methods based on modifier **public -> protected -> private**
- Move trait **LoaderTrait, SelectTrait, SoftDeletesTrait** functionality to **Query** class
- Move trait **AggregateTrait** functionality to **EntityQuery** class

#### Details change

##### Class EntityMapperInterface
- Rename method **entityName** to **name**
- Rename method **assignable** to **fillable**
- Set default value of parameter **$column** to **deleted_at** of method **useSoftDelete**
- Rename parameter **created_at** to **createdAt** of method **useTimestamp**
- Rename parameter **updated_at** to **updatedAt** of method **useTimestamp**

##### Class EntityMapper
- Rename attribute **entityName** to **name**
- Rename attribute **assignable** to **fillable**
- Rename attribute **softDelete** to **useSoftDelete**
- Rename attribute **timestamp** to **useTimestamp**
- Rename method **getEntityName** to **getName**
- Rename method **getClass** to **getEntityClass**
- Rename method **getTypeCasts** to **getCasts**
- Rename method **supportsSoftDelete** to **hasSoftDelete**
- Rename method **supportsTimestamp** to **hasTimestamp**
- Rename method **getAssignableColumns** to **getFillable**
- Rename method **getGuardedColumns** to **getGuarded**
- Add the possibility to use many event handlers for one event in method **on**


##### Class Entity
- Rename method **orm** to **mapper**
- add maggic methods **__get, __set, __isset**


##### Class PrimaryKey
- Rename method **valueFromDataMapper** to **getValueFromDataMapper**
- Rename method **valueFromEntity** to **getValueFromEntity**

##### Class Proxy
- Rename attribute **ormMethod** to **mapperMethod**
- Rename method **getDataMapper** to **getEntityDataMapper**


##### Class Relation
- Rename method **getLazyLoader** to **getLoader**

##### Class DataMapperInterface
- Rename method **assign** to **fill**
- Rename method **stale** to **refresh**
- Add methods **hasColumn** and **hasRelated**
- Remove parameter **$callback** of method **getRelated**

##### Class DataMapper
- Rename method **assign** to **fill**
- Rename method **linkOrUnlink** to **setLink**
- Rename attribute **stale** to **refresh**

##### Class EntityManager
- Rename method **resolveEntityMapper** to **getEntityMapper**

##### Class Query
- Rename attribute **have** to **havingStatement**
- Rename method **onlySoftDeleted** to **onlyDeleted**
- Rename method **withSoftDeleted** to **withDeleted**

##### Class EntityQuery
- Rename method **getLazyLoaders** to **getRelationLoaders**
- Remove parameter **$default** of method **transaction**

##### Class EntityManager
- Remove attribute **entityMappingCallbacks** and **compiler**
- Remove methods **__invoke, registerMappingCallback, getCompiler**
- Remove parameter **$callbacks** of method **__construct**
- Move methods **save, create, delete** to class **RepositoryInterface**