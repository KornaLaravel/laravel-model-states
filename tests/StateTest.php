<?php

use Illuminate\Support\Facades\Event;
use Spatie\ModelStates\Exceptions\InvalidConfig;
use Spatie\ModelStates\Tests\Dummy\AllowAllTransitionsStateWithNoRegisteredStates;
use Spatie\ModelStates\Events\StateChanged;
use Spatie\ModelStates\Exceptions\ClassDoesNotExtendBaseClass;
use Spatie\ModelStates\Tests\Dummy\CustomEventModelState\CustomEventModelStateB;
use Spatie\ModelStates\Tests\Dummy\CustomEventModelState\CustomInvalidEventModelStateB;
use Spatie\ModelStates\Tests\Dummy\CustomEventModelState\CustomInvalidStateChangedEvent;
use Spatie\ModelStates\Tests\Dummy\CustomEventModelState\CustomStateChangedEvent;
use Spatie\ModelStates\Tests\Dummy\ModelStates\ModelState;
use Spatie\ModelStates\Tests\Dummy\ModelStates\StateA;
use Spatie\ModelStates\Tests\Dummy\ModelStates\StateB;
use Spatie\ModelStates\Tests\Dummy\ModelStates\StateC;
use Spatie\ModelStates\Tests\Dummy\ModelStates\StateD;
use Spatie\ModelStates\Tests\Dummy\ModelStates\StateE;
use Spatie\ModelStates\Tests\Dummy\ModelStates\AnotherDirectory\StateF;
use Spatie\ModelStates\Tests\Dummy\ModelStates\AnotherDirectory\StateG;
use Spatie\ModelStates\Tests\Dummy\ModelStates\AnotherDirectory\StateH;
use Spatie\ModelStates\Tests\Dummy\OtherModelStates\StateX;
use Spatie\ModelStates\Tests\Dummy\OtherModelStates\StateY;
use Spatie\ModelStates\Tests\Dummy\TestModel;
use Spatie\ModelStates\Tests\Dummy\TestModelAllowAllTransitions;
use Spatie\ModelStates\Tests\Dummy\TestModelAllowAllTransitionsWithExplicitlyRegisteredStates;
use Spatie\ModelStates\Tests\Dummy\TestModelAllowAllTransitionsWithNoRegisteredStates;
use Spatie\ModelStates\Tests\Dummy\TestModelCustomEvent;
use Spatie\ModelStates\Tests\Dummy\TestModelCustomInvalidEvent;
use Spatie\ModelStates\Tests\Dummy\TestModelUpdatingEvent;
use Spatie\ModelStates\Tests\Dummy\TestModelWithCustomTransition;
use Spatie\ModelStates\Tests\Dummy\TestModelWithDefault;
use Spatie\ModelStates\Tests\Dummy\AllowAllTransitionsState;

it('resolve state class', function () {
    expect(ModelState::resolveStateClass(StateA::class))->toEqual(StateA::class);
    expect(ModelState::resolveStateClass(StateC::class))->toEqual(StateC::class);
    expect(ModelState::resolveStateClass(StateC::getMorphClass()))->toEqual(StateC::class);
    expect(ModelState::resolveStateClass(StateC::$name))->toEqual(StateC::class);
    expect(ModelState::resolveStateClass(StateD::class))->toEqual(StateD::class);
    expect(ModelState::resolveStateClass(StateD::getMorphClass()))->toEqual(StateD::class);
    expect(ModelState::resolveStateClass(StateD::$name))->toEqual(StateD::class);
    expect(ModelState::resolveStateClass(StateE::class))->toEqual(StateE::class);
    expect(ModelState::resolveStateClass(StateE::getMorphClass()))->toEqual(StateE::class);
    expect(ModelState::resolveStateClass(StateF::getMorphClass()))->toEqual(StateF::class);
    expect(ModelState::resolveStateClass(StateG::getMorphClass()))->toEqual(StateG::class);
    expect(ModelState::resolveStateClass(StateG::getMorphClass()))->toEqual(StateG::class);
    expect(ModelState::resolveStateClass(StateH::getMorphClass()))->toEqual(StateH::class);
    expect(ModelState::resolveStateClass(StateH::getMorphClass()))->toEqual(StateH::class);
});

it('transitionable states', function () {
    $modelA = TestModel::make(['state' => StateA::class]);

    expect(
        [
            StateB::getMorphClass(),
            StateC::getMorphClass(),
            StateD::getMorphClass(),
            StateF::getMorphClass(),
        ]
    )->toEqual($modelA->state->transitionableStates());

    $modelB = TestModelWithDefault::create([
        'state' => StateC::class,
    ]);

    expect($modelB->state->transitionableStates())->toEqual([]);
});

it('transitionable states with custom transition', function () {
    $model = TestModelWithCustomTransition::create(['state' => StateX::class]);
    expect($model->state->transitionableStates())->toBe([StateY::class]);
});

it('returns transitionable state instances', function () {
    $model = TestModel::create([
        'state' => StateA::class,
    ]);

    $stateInstances = $model->state->transitionableStateInstances();

    expect($stateInstances)->toBeArray();
    expect($stateInstances)->each->toBeObject();
    expect($stateInstances)->toContainOnlyInstancesOf(ModelState::class);

    // Test that the actual instances match what we expect based on allowed transitions
    $stateClassNames = array_map(fn($instance) => get_class($instance), $stateInstances);
    expect($stateClassNames)->toEqual([
        StateB::class,
        StateC::class,
        StateD::class,
        StateF::class,
    ]);


    $modelB = TestModelWithDefault::create([
        'state' => StateC::class,
    ]);

    expect($modelB->state->transitionableStateInstances())->toEqual([]);
});


it('equals', function () {
    $modelA = TestModelWithDefault::create();

    $modelB = TestModelWithDefault::create();

    expect($modelA->state->equals($modelB->state))->toBeTrue();

    $modelA = TestModelWithDefault::create();

    $modelB = TestModelWithDefault::create([
        'state' => StateC::class,
    ]);

    expect($modelA->state->equals($modelB->state))->toBeFalse();

    expect($modelA->state->equals(StateA::class))->toBeTrue();
});

it('can transition to', function () {
    $state = new StateA(new TestModel());
    $state->setField('state');

    expect($state->canTransitionTo(StateB::class))->toBeTrue();
    expect($state->canTransitionTo(StateC::class))->toBeTrue();
    expect($state->canTransitionTo(StateF::class))->toBeTrue();

    $state = new StateB(new TestModel());
    $state->setField('state');

    expect($state->canTransitionTo(StateB::class))->toBeFalse();
    expect($state->canTransitionTo(StateA::class))->toBeFalse();
});

it('get states', function () {
    $states = TestModelWithDefault::getStates();

    expect(
        [
            'state' => [
                StateA::getMorphClass(),
                StateB::getMorphClass(),
                StateC::getMorphClass(),
                StateD::getMorphClass(),
                StateE::getMorphClass(),
                StateF::getMorphClass(),
                StateG::getMorphClass(),
                StateH::getMorphClass(),
            ],
        ],
    )->toEqual($states->toArray());
});

it('get states for', function () {
    $states = TestModelWithDefault::getStatesFor('state');

    expect(
        [
            StateA::getMorphClass(),
            StateB::getMorphClass(),
            StateC::getMorphClass(),
            StateD::getMorphClass(),
            StateE::getMorphClass(),
            StateF::getMorphClass(),
            StateG::getMorphClass(),
            StateH::getMorphClass(),
        ],
    )->toEqual($states->toArray());
});

it('get default states', function () {
    $states = TestModelWithDefault::getDefaultStates();

    expect(
        [
            'state' => StateA::getMorphClass(),
        ],
    )->toEqual($states->toArray());
});

it('get default states for', function () {
    $defaultState = TestModelWithDefault::getDefaultStateFor('state');

    expect($defaultState)->toEqual(StateA::getMorphClass());
});

it('make', function () {
    $stateA = ModelState::make(StateA::class, new TestModel());

    expect($stateA)->toBeInstanceOf(StateA::class);

    $stateC = ModelState::make('C', new TestModel());

    expect($stateC)->toBeInstanceOf(StateC::class);

    $stateD = ModelState::make(4, new TestModel());

    expect($stateD)->toBeInstanceOf(StateD::class);
});

it('all', function () {
    expect(
        [
            StateA::getMorphClass() => StateA::class,
            StateB::getMorphClass() => StateB::class,
            StateC::getMorphClass() => StateC::class,
            StateD::getMorphClass() => StateD::class,
            StateE::getMorphClass() => StateE::class,
            StateF::getMorphClass() => StateF::class,
            StateG::getMorphClass() => StateG::class,
            StateH::getMorphClass() => StateH::class,
        ]
    )->toEqual(ModelState::all()->toArray());
});

it('default is set when constructing a new model', function () {
    $model = new TestModel();

    expect($model->state->equals(StateA::class))->toBeTrue();
});

it('default is set when creating a new model', function () {
    $model = TestModel::create();

    expect($model->state->equals(StateA::class))->toBeTrue();
});

it('get model', function () {
    $stateA = ModelState::make(StateA::class, new TestModel());

    expect($stateA->getModel())->toBeInstanceOf(TestModel::class);
});

it('get field', function () {
    $model = TestModel::create();

    expect($model->state->getField())->toEqual('state');
});

it('can override default transition', function () {
    Event::fake();

    config()->set(
        'model-states.default_transition',
        \Spatie\ModelStates\Tests\Dummy\Transitions\CustomDefaultTransition::class
    );

    TestModel::create()->state->transitionTo(StateB::class);

    Event::assertNotDispatched(TestModelUpdatingEvent::class);
});

it('can use attributes with custom default transition', function () {
    Event::fake();
    $customDefaultTransitionClass = \Spatie\ModelStates\Tests\Dummy\Transitions\CustomDefaultTransitionWithAttributes::class;

    config()->set('model-states.default_transition', $customDefaultTransitionClass);

    TestModel::create()->state->transitionTo(StateB::class, true);

    Event::assertDispatched(
        StateChanged::class,
        function (StateChanged $event) use ($customDefaultTransitionClass) {
            $transition = $event->transition;
            return $transition instanceof $customDefaultTransitionClass
                && $transition->silent === true;
        }
    );
});

it('can emit a custom state changed event', function () {
    Event::fake();

    $model = TestModelCustomEvent::create();

    $model->state->transitionTo(CustomEventModelStateB::class);

    Event::assertDispatched(CustomStateChangedEvent::class);
});

it('emits the standard state changed event', function () {
    Event::fake();

    $model = TestModel::create();

    $model->state->transitionTo(StateB::class);

    Event::assertDispatched(StateChanged::class);
});

it('includes the field name in the state changed event', function () {
    Event::fake();

    $model = TestModel::create();

    $model->state->transitionTo(StateB::class);

    Event::assertDispatched(StateChanged::class, function (StateChanged $stateChanged) {
        return $stateChanged->field == 'state';
    });
});

it('should throw exception when custom state changed event does not extend StateChanged', function () {
    Event::fake();

    $model = TestModelCustomInvalidEvent::create();

    $this->expectException(ClassDoesNotExtendBaseClass::class);
    $this->expectExceptionMessage('Class ' . CustomInvalidStateChangedEvent::class . ' does not extend the `' . StateChanged::class . '` base class.');

    $model->state->transitionTo(CustomInvalidEventModelStateB::class);
});

it('should allow all transitions', function () {
    $model = TestModelAllowAllTransitions::create();

    expect($model->state)
        ->canTransitionTo(AllowAllTransitionsState\StateA::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsState\StateB::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsState\StateC::class)->toBeTrue();

    $model->state->transitionTo(AllowAllTransitionsState\StateB::class);

    expect($model->state)
        ->canTransitionTo(AllowAllTransitionsState\StateA::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsState\StateB::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsState\StateC::class)->toBeTrue();

    $model->state->transitionTo(AllowAllTransitionsState\StateC::class);

    expect($model->state)
        ->canTransitionTo(AllowAllTransitionsState\StateA::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsState\StateB::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsState\StateC::class)->toBeTrue();
});

it('should allow all transitions for explicitly registered states', function () {
    $model = TestModelAllowAllTransitionsWithExplicitlyRegisteredStates::create();

    expect($model->state)
        ->canTransitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateAWithNoRegisteredStates::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateBWithNoRegisteredStates::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateCWithNoRegisteredStates::class)->toBeTrue();

    $model->state->transitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateBWithNoRegisteredStates::class);

    expect($model->state)
        ->canTransitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateAWithNoRegisteredStates::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateBWithNoRegisteredStates::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateCWithNoRegisteredStates::class)->toBeTrue();

    $model->state->transitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateCWithNoRegisteredStates::class);

    expect($model->state)
        ->canTransitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateAWithNoRegisteredStates::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateBWithNoRegisteredStates::class)->toBeTrue()
        ->canTransitionTo(AllowAllTransitionsStateWithNoRegisteredStates\StateCWithNoRegisteredStates::class)->toBeTrue();
});

it('should throw exception when allowing all transitions when there are no registered states', function () {
    $this->expectException(InvalidConfig::class);
    $this->expectExceptionMessage('No states registered for ' . AllowAllTransitionsStateWithNoRegisteredStates\AllowAllTransitionsStateWithNoRegisteredStates::class);

    TestModelAllowAllTransitionsWithNoRegisteredStates::create();
});
