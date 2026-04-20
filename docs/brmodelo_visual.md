# BrModelo Visual Para Copiar

Este arquivo foi organizado para voce montar o modelo no BrModelo com menos retrabalho.

## Ordem sugerida no BrModelo

1. Crie as entidades: `usuarios`, `clientes`, `quartos`, `tipos_acomodacao`, `reservas`, `frigobar`, `consumo_frigobar`, `permissoes`, `logs_sistema`.
2. Marque as chaves primarias.
3. Crie os relacionamentos principais: `reservas`, `frigobar`, `consumo_frigobar`.
4. Adicione os relacionamentos logicos: `tipos_acomodacao -> quartos` e `usuarios -> permissoes`.
5. Deixe `logs_sistema` isolada, porque hoje ela nao possui FK.

## Entidades prontas para desenhar

### usuarios

```text
USUARIOS
- id (PK)
- login
- senha
- perfil
- status
- nivel
```

### clientes

```text
CLIENTES
- id (PK)
- nome
- data_nascimento
- cpf
- email
- telefone
- estado
- cidade
- status
```

### quartos

```text
QUARTOS
- id (PK)
- quarto
- tipo
- preco
- descricao
- capacidade
- vagas_estacionamento
- status
```

### tipos_acomodacao

```text
TIPOS_ACOMODACAO
- id (PK)
- nome
- status
```

### reservas

```text
RESERVAS
- id (PK)
- quarto_id (FK)
- cliente_id (FK)
- usuario_id (FK, opcional)
- data_reserva
- valor_total
- data_checkin
- hora_checkin
- data_checkout
- hora_checkout
- status
- data_finalizacao
- data_cancelamento
- motivo_cancelamento
```

### frigobar

```text
FRIGOBAR
- id (PK)
- nome
- quantidade
- valor
- quarto_id (FK)
- status
- status_frigobar
```

### consumo_frigobar

```text
CONSUMO_FRIGOBAR
- id (PK)
- reserva_id (FK)
- frigobar_id (FK)
- quantidade
- valor_total
```

### permissoes

```text
PERMISSOES
- id (PK)
- perfil
- pagina
- permitido
```

### logs_sistema

```text
LOGS_SISTEMA
- id (PK)
- data_hora
- acao
- mensagem
```

## Relacionamentos para ligar no BrModelo

### 1) clientes -> reservas

```text
CLIENTES 1 ----- N RESERVAS
```

- Um cliente pode ter varias reservas.
- Cada reserva pertence a um cliente.

### 2) quartos -> reservas

```text
QUARTOS 1 ----- N RESERVAS
```

- Um quarto pode aparecer em varias reservas ao longo do tempo.
- Cada reserva usa um unico quarto.

### 3) usuarios -> reservas

```text
USUARIOS 1 ----- N RESERVAS
```

- Um usuario pode registrar varias reservas.
- Em `reservas`, `usuario_id` pode ser opcional.

### 4) quartos -> frigobar

```text
QUARTOS 1 ----- N FRIGOBAR
```

- Um quarto pode ter varios itens no frigobar.
- Cada item de frigobar pertence a um unico quarto.

### 5) reservas -> consumo_frigobar

```text
RESERVAS 1 ----- N CONSUMO_FRIGOBAR
```

- Uma reserva pode gerar varios consumos.
- Cada consumo pertence a uma unica reserva.

### 6) frigobar -> consumo_frigobar

```text
FRIGOBAR 1 ----- N CONSUMO_FRIGOBAR
```

- Um item de frigobar pode aparecer em varios consumos.
- Cada consumo aponta para um item de frigobar.

### 7) tipos_acomodacao -> quartos

```text
TIPOS_ACOMODACAO 1 ----- N QUARTOS
```

- Esse relacionamento e logico no sistema atual.
- No banco, `quartos.tipo` guarda texto e ainda nao existe `tipo_id`.

### 8) usuarios -> permissoes

```text
USUARIOS 1 ----- N PERMISSOES
```

- Esse relacionamento deve ser entendido pelo campo `perfil`.
- No banco atual ele e logico, nao fisico.

## Esboco visual para reproduzir

```text
TIPOS_ACOMODACAO
        |
        | 1:N
        |
      QUARTOS ---------------- FRIGOBAR
        |                          |
        | 1:N                      | 1:N
        |                          |
        +------ RESERVAS ----------+------ CONSUMO_FRIGOBAR
                 /   \
                /     \
             1:N       1:N
              /         \
             /           \
       CLIENTES         USUARIOS -------- PERMISSOES


LOGS_SISTEMA
(entidade isolada)
```

## Dica de posicionamento na tela

```text
[TIPOS_ACOMODACAO]      [USUARIOS] ------ [PERMISSOES]
        |                    |
        |                    |
     [QUARTOS] --------- [RESERVAS] -------- [CLIENTES]
        |
        |
    [FRIGOBAR] -------- [CONSUMO_FRIGOBAR]

               [LOGS_SISTEMA]
```

## O que marcar como observacao no BrModelo

- `usuario_id` em `reservas` e opcional.
- `tipos_acomodacao -> quartos` e um relacionamento logico.
- `usuarios -> permissoes` tambem e logico, baseado em `perfil`.
- `logs_sistema` nao depende de outra tabela.
