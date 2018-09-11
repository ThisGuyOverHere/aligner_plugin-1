import React, {Component} from 'react';
import ProjectActions from "../../../Actions/Project.actions";
import PropTypes from "prop-types";
import SplitCharComponent from "./SplitCharComponent/SplitCharComponent.component";
import SplitDivisor from "./SplitDivisor/SplitDivisor.component";

class SplitComponent extends Component {
    static propTypes = {
        segment: PropTypes.object,
    };

    constructor(props) {
        super(props);
        this.state = {
            splitModalStatus: false,
            segmentContent: this.props.segment.content_clean,
            chars: this.props.segment.content_clean.split(""),
            splits: {},
            charDictionary: this.fillDictionaries().charDictionary,
            wordDictionary: this.fillDictionaries().wordDictionary,
            temporarySplitPosition: -1,
        };
    }

    componentDidMount() {

    }

    componentWillUnmount() {

    }

    render = () => {
        return (
            <div>
                <div>
                    <div className="overlay" onClick={this.onCloseSplitModal}>
                    </div>
                    <div className="splitContainer">
                        <div className="header">
                            <div className="sx-header">
                                <img src="/public/img/logo-ico.png"></img>
                                <h1>Split Segment</h1>
                            </div>
                            <div className="dx-header">
                            <span onClick={this.onCloseSplitModal}>
                                <i className="icon window close"></i>
                            </span>
                            </div>
                        </div>
                        <div className="content">
                            <p id="toSplit" onMouseLeave={() => this.onCharHover(-1)}>
                                {this.renderItems()}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    renderItems = () => {
        let items = [];
        let countSplittedItems = 0;
        let range = [-1,-1];
        const wordIndex = this.state.charDictionary[this.state.temporarySplitPosition];

        if (this.state.temporarySplitPosition > -1){
            this.leftSigned(wordIndex,range);
            this.rightSigned(wordIndex,range);
        }

        this.state.chars.map((element, index) => {
            countSplittedItems++;
            items.push(<SplitCharComponent
                word={element}
                key={countSplittedItems}
                signed={this.state.charDictionary[index] >= range[0] && this.state.charDictionary[index] <= range[1]}
                position={index}
                onClick={this.onCharClick}
                onHover={this.onCharHover}
            />);
            if (this.state.splits[index]) {
                countSplittedItems++;
                items.push(<SplitDivisor
                    key={countSplittedItems}
                    position={index}
                    isIcon={true}
                    onClick={this.onCharClick}
                />)
            } else if (this.state.temporarySplitPosition === index) {
                countSplittedItems++;
                items.push(<SplitDivisor
                    key={countSplittedItems}
                    temporary={true}
                    position={index}
                    onClick={this.onCharClick}
                />)
            }
        });

        return items;

    };

    onCloseSplitModal = () => {
        ProjectActions.splitModalStatus(false);
        ProjectActions.setSegmentToSplit({});
    };

    /**
     * with this method we track the position of setted split cursor our in phrase
     * @param index
     */
    onCharClick = (index) => {
        let splits = this.state.splits;
        splits[index] ? splits[index] = false : splits[index] = true;
        this.setState({
            splits: splits
        })
    };

    /**
     * with this method we track the real time split cursor position on hover
     * @param index
     */
    onCharHover = (index) => {
        if(this.state.splits[index]){
            index = -1
        }
        this.setState({
            temporarySplitPosition: index,
        });
    };

    /**
     * this function will create and fill our dictionaries,
     * ready to use for ours calculations, to perform split operation.
     * @returns {{charDictionary: {}, wordDictionary: {}}}
     */
    fillDictionaries = () => {
        // words in our phrase splitted by space
        const words = this.props.segment.content_clean.split(" ");
        // dictionaries structure
        let dictionaries = {
            charDictionary : {},
            wordDictionary : {},
        };
        let charIndex = 0;
        let wordIndex = 0;
        // here we'll fill our dictionaries
        for (let x = 0; x < words.length; x++) {
            // for each word in words, we split our word in characters
            const word = words[x].split("");
            for (let y = 0; y < word.length; y++) {
                // we associate the character with the word
                dictionaries.charDictionary[charIndex] = wordIndex;
                charIndex++;
                /*
                    at the end of the chars count for this word,
                    we update wordDictionaries with word characters count
                */
                if (y === word.length - 1) {
                    dictionaries.wordDictionary[wordIndex] = y + 1;
                }
            }
            /*
                while we have word in words after char count,
                update wordindex, charDictionary, charindex...
             */
            if (x < words.length - 1) {
                wordIndex++;
                dictionaries.charDictionary[charIndex] = wordIndex;
                dictionaries.wordDictionary[wordIndex] = 1;
                charIndex++;
            }
            wordIndex++;
        }
        return dictionaries;
    };

    /**
     * we will calculate the characters that will be signed at the right
     * @param {Number} wordIndex
     * @param {Array} range
     */
    rightSigned = (wordIndex, range) => {
        for(let index = wordIndex; index < this.state.chars.length-2; index++){
            range[1] = index;
            if(this.state.wordDictionary[index+1] > 1){
                range[1] = index+1;
                break
            }
        }
    };
    /**
     * we will calculate the characters that will be signed at the left
     * @param {Number} wordIndex
     * @param {Array} range
     */
    leftSigned = (wordIndex, range) => {
        for(let index = wordIndex; index > 0; index--){
            range[0] = index;
            if(this.state.wordDictionary[index] > 1){
                range[0] = index-1;
                break
            }
        }
    };
}

export default SplitComponent;