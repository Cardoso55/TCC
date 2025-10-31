CREATE TABLE Usuarios_TBL (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    nivel VARCHAR(50),
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_login DATETIME,
    ativo BOOLEAN DEFAULT TRUE,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL
 );
 CREATE TABLE Produtos_TBL (
    id_produto INT PRIMARY KEY AUTO_INCREMENT,
    codigo_produto VARCHAR(50) UNIQUE NOT NULL,
    categoria VARCHAR(50),
    descricao TEXT,
    nome VARCHAR(100) NOT NULL,
    imagem_url VARCHAR(255),
    preco_unitario DECIMAL(10,2) NOT NULL,
    unidade_medida VARCHAR(50),
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
 );
 CREATE TABLE Estoque_TBL (
    id_estoque INT PRIMARY KEY AUTO_INCREMENT,
    quantidade_atual INT DEFAULT 0,
    quantidade_minima INT DEFAULT 0,
    quantidade_maxima INT DEFAULT 0,
    quantidade_baixo BOOLEAN DEFAULT FALSE,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    idProdutos_TBL INT,
    FOREIGN KEY (idProdutos_TBL) REFERENCES Produtos_TBL(id_produto)
 );
 CREATE TABLE Compras_TBL (
    id_compra INT PRIMARY KEY AUTO_INCREMENT,
    fornecedor VARCHAR(100),
    valor_total DECIMAL(10,2),
    data_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
    idUsuarios_TBL INT,
    FOREIGN KEY (idUsuarios_TBL) REFERENCES Usuarios_TBL(id_usuario)
 );
 CREATE TABLE PedidosReposicao_TBL (
    id_pedido INT PRIMARY KEY AUTO_INCREMENT,
    status VARCHAR(50),
    fornecedor VARCHAR(100),
    data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_recebimento DATETIME,
    idUsuarios_TBL INT,
    FOREIGN KEY (idUsuarios_TBL) REFERENCES Usuarios_TBL(id_usuario)
 );
 CREATE TABLE Checklist_TBL (
    id_checklist INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(50),
    conteudo TEXT,
    status VARCHAR(50),
    observacao TEXT,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_confirmacao DATETIME,
    idUsuarios_TBL INT,
    idPedidosReposicao_TBL INT,
    idCompra_TBL INT,
    idProduto_TBL INT,
    FOREIGN KEY (idUsuarios_TBL) REFERENCES Usuarios_TBL(id_usuario),
    FOREIGN KEY (idPedidosReposicao_TBL) REFERENCES PedidosReposicao_TBL(id_pedido),
    FOREIGN KEY (idCompra_TBL) REFERENCES Compras_TBL(id_compra),
    FOREIGN KEY (idProduto_TBL) REFERENCES Produtos_TBL(id_produto)
 );
 CREATE TABLE Alertas_TBL (
    id_alerta INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(50),
    mensagem TEXT,
    nivel_prioridade VARCHAR(50),
    enviado_para INT,
    status VARCHAR(50),
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    idUsuario_TBL INT,
    idProdutos_TBL INT,
    FOREIGN KEY (idUsuario_TBL) REFERENCES Usuarios_TBL(id_usuario),
    FOREIGN KEY (enviado_para) REFERENCES Usuarios_TBL(id_usuario),
    FOREIGN KEY (idProdutos_TBL) REFERENCES Produtos_TBL(id_produto)
 );
 CREATE TABLE Alteracoes_TBL (
    id_solicitacao INT PRIMARY KEY AUTO_INCREMENT,
    quantidade INT,
    tipo VARCHAR(50),
    status VARCHAR(50),
    aprovado_por VARCHAR(100),
    observacao TEXT,
    data_solicitacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_aprovacao DATETIME,
    idUsuarios_TBL INT,
    idProdutos_TBL INT,
    idEstoque_TBL INT,
    FOREIGN KEY (idUsuarios_TBL) REFERENCES Usuarios_TBL(id_usuario),
    FOREIGN KEY (idProdutos_TBL) REFERENCES Produtos_TBL(id_produto),
    FOREIGN KEY (idEstoque_TBL) REFERENCES Estoque_TBL(id_estoque)
 );
 CREATE TABLE Movimentacoes_TBL (
    id_movimentacao INT PRIMARY KEY AUTO_INCREMENT,
    quantidade INT,
    tipo VARCHAR(50),
    origem VARCHAR(100),
    observacao TEXT,
    data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    idUsuarios_TBL INT,
    idEstoque_TBL INT,
    idProdutos_TBL INT,
    FOREIGN KEY (idUsuarios_TBL) REFERENCES Usuarios_TBL(id_usuario),
    FOREIGN KEY (idEstoque_TBL) REFERENCES Estoque_TBL(id_estoque),
    FOREIGN KEY (idProdutos_TBL) REFERENCES Produtos_TBL(id_produto)
 );