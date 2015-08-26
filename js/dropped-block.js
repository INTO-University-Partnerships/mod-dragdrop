/**
 * Places blocks that have been dropped on to the drop area/canvas
 */
export default function () {

    /**
     * properties of the canvas
     * @type {{object}}
     */
    this.canvas = {};

    /**
     * blocks that have been dropped on to the area
     * @type {Array}
     */
    this.blocks = [];

    /**
     * blocks to place
     * @type {Array}
     */
    this.blocksToPlace = [];

    /**
     * Jquery elements corresponding to the blocks
     * @type {Array}
     */
    this.elements = [];

    /**
     * add a dropped block and position it
     * @param dropped
     * @param element
     */
    this.addDroppedBlock = function (dropped, element) {
        this.blocks[dropped.wordblockid] = dropped;
        this.elements[dropped.wordblockid] = element;
        this.positionBlock(dropped.wordblockid);
        return dropped;
    };

    /**
     * set the canvas object
     * @param canvas
     */
    this.setCanvas = function (canvas) {
        this.canvas = canvas;
    };

    /**
     * utility method
     * @param arrCloned
     * @returns {Array}
     */
    this.cloneArray = function (arrToClone) {
        var clone = [];
        for (var i in arrToClone) {
            if (arrToClone[i]) {
                clone[i] = jQuery.extend({}, arrToClone[i]);
            }
        }
        return clone;

    };

    /**
     * get all blocks ids with which a given block has collided
     * @param block
     * @returns {Array}
     */
    this.getCollisions = function (block) {

        // clone and sort
        var blocks = this.cloneArray(this.blocksToPlace);
        blocks.sort(this.sortBlocksByPosition);

        var collisions = [];
        blocks.forEach(compBlock => {
            if (compBlock.wordblockid === block.wordblockid) {
                return;
            }
            if (compBlock.top !== block.top) {
                return;
            }
            if ((compBlock.left + compBlock.width > block.left) && (block.left + block.width > compBlock.left)) {
                collisions.push(compBlock.wordblockid);
            }
        });
        return collisions;
    };

    /**
     * get blocks in order
     * @returns {Array}
     */
    this.getSortedBlocks = function () {

        // clone and sort
        var blocks = this.blocks.filter(
            function (element) {
                return element !== null;
            }
        );
        blocks.sort(this.sortBlocksByPosition);
        return blocks;
    };

    /**
     * sorts blocks top to bottom / left to right
     * @param a
     * @param b
     * @returns {number}
     */
    this.sortBlocksByPosition = function (a, b) {
        if (a.top < b.top) {
            return -1;
        }
        if (a.top > b.top) {
            return 1;
        }
        if (a.left > b.left) {
            return 1;
        }
        if (a.left < b.left) {
            return -1;
        }
        return 0;
    };

    /**
     * position an element on the page
     * @param block
     */
    this.positionElement = function (block) {
        var element = this.elements[block.wordblockid];
        element.offset({
            top: block.top + this.canvas.top,
            left: block.left + this.canvas.left
        });
    };

    /**
     * positions elements on the page
     */
    this.positionElements = function () {
        for (var i in this.blocksToPlace) {
            if (this.blocksToPlace[i] === null) {
                continue;
            }
            this.positionElement(this.blocksToPlace[i]);
        }
    };

    /**
     * work out where the block should be positioned and resolve any collisions
     * @param id
     */
    this.positionBlock = function (id) {

        // take a clone of the blocks in case we need to cancel
        this.blocksToPlace = this.cloneArray(this.blocks);
        var block = this.blocksToPlace[id];
        var collisions = this.getCollisions(block);
        // nothing to do
        if (collisions.length === 0) {
            this.blocksToPlace = [];
            this.positionElement(block);
            return;
        }
        // resolve against first collision (the first one from the left)
        var collidedBlock = this.blocksToPlace[collisions[0]];
        if (this.intersectsOnTheRight(block, collidedBlock)) {
            this.resolveCollisionRight(block, collidedBlock);
        }
        else {
            this.resolveCollisionLeft(block, collidedBlock);
        }

        // position the elements
        this.positionElements();
        this.blocks = this.cloneArray(this.blocksToPlace);
        this.blocksToPlace = [];
    };

    /**
     * resolve the collision to the right and cascade
     * @param block
     * @param collidedBlock
     */
    this.resolveCollisionRight = function (block, collidedBlock) {
        var spaceOnLineRight = this.isSpaceOnLineRight(collidedBlock.left + collidedBlock.width, block.width);

        if (!spaceOnLineRight) {
            block.left = this.canvas.width + 2 * this.canvas.blockMargin - block.width;
            this.cascadeLeft(block);
        }
        else {
            this.moveRightOfPosition(block, (collidedBlock.left + collidedBlock.width));
        }
        if (!this.cascadeRight(block)) {

            // this has failed due to lack of space. resolve to the left instead.
            this.blocksToPlace = this.cloneArray(this.blocks);
            block = this.blocksToPlace[block.wordblockid];
            this.moveLeftOfPosition(block, (collidedBlock.left + collidedBlock.width));
            this.cascadeLeft(block);
        }
    };

    /**
     * resolve the collision to the left and cascade
     * @param block
     * @param collidedBlock
     */
    this.resolveCollisionLeft = function (block, collidedBlock) {
        var spaceOnLineLeft = this.isSpaceOnLineLeft(collidedBlock.left, block.width);

        if (!spaceOnLineLeft) {
            block.left = 0;
        }
        else {
            this.moveLeftOfPosition(block, collidedBlock.left);
        }
        if (!this.cascadeLeft(block)) {

            // this has failed due to lack of space. resolve to the right instead.
            this.blocksToPlace = this.cloneArray(this.blocks);
            block = this.blocksToPlace[block.wordblockid];
            this.cascadeRight(block);
        }
    };

    /**
     * recursive function for cascading and resolving collisions to the left
     * @param block
     * @returns {boolean}
     */
    this.cascadeLeft = function (block) {
        var collisions = this.getCollisions(block);
        if (collisions.length === 0) {
            return true;
        }
        var collidedWidth = 0;
        collisions.forEach(collision => {
            collidedWidth += this.blocks[collision].width;
        });

        var line = (block.top / this.canvas.lineHeight) + 1;

        // first line and there is no more space
        if (line === 1 && !this.isSpaceOnLineLeft(block.left, collidedWidth)) {
            return false;
        }
        var left = block.left;
        collisions.forEach(collision => {
            var collidedBlock = this.blocksToPlace[collision];
            this.moveLeftOfPosition(collidedBlock, left);
            if (!this.cascadeLeft(collidedBlock)) {
                return false;
            }
        });
        return true;
    };

    /**
     * recursive function for cascading and resolving collisions to the right
     * @param block
     * @returns {boolean}
     */

    this.cascadeRight = function (block) {
        var collisions = this.getCollisions(block);
        if (collisions.length === 0) {
            return true;
        }

        // reverse to read collisions right to left
        collisions.reverse();
        var collidedWidth = 0;
        collisions.forEach(collision => {
            collidedWidth += this.blocks[collision].width;
        });
        var line = (block.top / this.canvas.lineHeight) + 1;
        // last line and no more space
        if (line === this.canvas.lines && !this.isSpaceOnLineRight(block.left + block.width, collidedWidth)) {
            return false;
        }
        var left = block.left;
        collisions.forEach(collision => {
            var collidedBlock = this.blocksToPlace[collision];
            this.moveRightOfPosition(collidedBlock, (block.width + left));
            if (!this.cascadeRight(collidedBlock)) {
                return false;
            }
        });
        return true;
    };

    /**
     * whether a block intersects a block with which it has collided on the right
     * @param block
     * @param collidedBlock
     * @returns {boolean}
     */
    this.intersectsOnTheRight = function (block, collidedBlock) {
        return (collidedBlock.left + (collidedBlock.width / 2) < block.left + (block.width / 2));
    };

    /**
     * move a block to the right of the given position
     * or jump to a new line
     * @param block
     * @param position
     */
    this.moveRightOfPosition = function (block, position) {
        if (this.isSpaceOnLineRight(position, block.width)) {
            block.left = position;
        }
        else {
            block.left = 0;
            block.top += this.canvas.lineHeight;
        }
    };

    /**
     * move a block to the left of the given position
     * or jump to a previous line
     * @param block
     * @param position
     */
    this.moveLeftOfPosition = function (block, position) {
        if (this.isSpaceOnLineLeft(position, block.width)) {
            block.left = position - block.width;
        }
        else {
            block.left = (this.canvas.width + 2 * this.canvas.blockMargin) - block.width;
            block.top -= this.canvas.lineHeight;
        }
    };

    /**
     * whether there is space on the line, to the left
     * @param position
     * @param width
     * @returns {boolean}
     */
    this.isSpaceOnLineLeft = function (position, width) {
        return position >= width;
    };

    /**
     * whether there is space on the line, to the right
     * @param position
     * @param width
     * @returns {boolean}
     */
    this.isSpaceOnLineRight = function (position, width) {
        var availableSpace = (this.canvas.width + 2 * this.canvas.blockMargin) - position;
        return (availableSpace >= width);
    };
}
