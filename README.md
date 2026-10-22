# AfterDark

**Uma plataforma dedicada aos amantes do terror.**

O AfterDark é uma plataforma de filmes de terror desenvolvida como projeto acadêmico do curso Técnico em Desenvolvimento de Sistemas do SENAI Félix Guisard. O projeto tem como objetivo reunir filmes de terror em um só lugar, proporcionando uma experiência visual imersiva, organizada e intuitiva para os usuários.

## Tema do projeto

O tema escolhido é o cinema de terror, abrangendo diferentes subgêneros, como:

- Terror psicológico;
- Sobrenatural e paranormal;
- Slasher;
- Gore e splatter;
- Body horror (terror corporal);
- Found footage (filmagem encontrada);
- Folk horror (terror folclórico);
- Terror cósmico;
- Terror gótico.

A identidade visual utiliza tons escuros, com predominância de preto, vermelho-sangue e cinza, inspirados na atmosfera cinematográfica dos filmes de terror.

## Participantes

| Participante | Responsabilidade |
|---|---|
| [Ana Clara Carvalho Ferreira] | [Responsabilidade no projeto] |
| [Emanuelly da Silva Carvalho] | [Responsabilidade no projeto] |
| [Geovanna Inês Ferreira da Silva] | [Responsabilidade no projeto] |
| [Isabela Paiva Oliveira] | [Responsabilidade no projeto] |

## Funcionalidades

- Catálogo de filmes de terror;
- Organização dos filmes por categorias;
- Exibição de informações dos filmes;
- Cadastro e login de usuários;
- Lista de filmes favoritos;
- Integração com a API do The Movie Database (TMDB) para consultar informações e imagens dos filmes.

## Tecnologias utilizadas

- **HTML5:** estrutura das páginas;
- **CSS3:** estilização e identidade visual;
- **JavaScript:** interatividade da interface;
- **PHP:** processamento e lógica do sistema;
- **MySQL:** armazenamento dos dados;
- **XAMPP:** ambiente de desenvolvimento local;
- **phpMyAdmin:** gerenciamento do banco de dados;
- **TMDB API:** consulta de informações sobre filmes.

## Como instalar e executar

### 1. Pré-requisitos

- XAMPP instalado;
- Navegador web;
- Arquivos do projeto;
- Credenciais de acesso à API do TMDB, quando necessárias.

### 2. Baixar o projeto

Clone o repositório utilizando o comando:

```bash
git clone URL_DO_REPOSITORIO
```

Ou baixe o projeto em formato ZIP pelo GitHub e extraia os arquivos.

### 3. Configurar a pasta do projeto

Coloque a pasta `AfterDark` dentro do diretório `htdocs` do XAMPP.

No Windows, o caminho normalmente será:

```text
C:\xampp\htdocs\AfterDark
```

### 4. Iniciar os serviços

Abra o painel de controle do XAMPP e inicie os serviços Apache e MySQL.

### 5. Configurar o banco de dados

1. Acesse `http://localhost/phpmyadmin`.
2. Crie um banco de dados chamado `afterdark`, caso ainda não exista.
3. Importe o arquivo SQL do banco de dados fornecido com o projeto.
4. Confira as configurações de conexão no arquivo `conexao.php`.

### 6. Configurar a API do TMDB

Configure o token de acesso do TMDB no arquivo de configuração utilizado pelo projeto. Utilize suas próprias credenciais e não publique tokens ou senhas no repositório.

### 7. Acessar o site

Após iniciar os serviços e configurar o banco de dados, acesse:

- Página inicial: `http://localhost/AfterDark/index.php`
- Catálogo de filmes: `http://localhost/AfterDark/catalogo.php`

## Estrutura do projeto

A estrutura principal pode incluir os seguintes arquivos e diretórios:

```text
AfterDark/
├── assets/
│   └── img/
├── index.php
├── catalogo.php
├── favoritos.php
├── login.php
├── cadastro.php
├── conexao.php
├── config.php
├── tmdb.php
└── README.md
```

A estrutura pode variar conforme os arquivos efetivamente implementados no projeto.

## Informações acadêmicas

**Instituição:** SENAI Félix Guisard  
**Curso:** Técnico em Desenvolvimento de Sistemas  
**Projeto:** AfterDark — Plataforma de Filmes de Terror

---

Desenvolvido como projeto acadêmico, com foco na aplicação prática de desenvolvimento web, integração com banco de dados e consumo de APIs.