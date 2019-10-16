import React, {Component} from 'react';
import PropTypes from "prop-types";

class Truncate extends Component {
    static propTypes = {
        title: PropTypes.string
    };

    constructor(props) {
        super(props);
        this.state = {
            calculatedTitle: null,
            hiddenText: null,
            trimCounter: 4
        };
        this.container = React.createRef();
        this.text = React.createRef();
        this.hiddenText = React.createRef();
    }

    componentDidUpdate(prevProps) {
        const {handleTitleDisplay} = this;
        const {calculatedTitle} = this.state;
        if (!calculatedTitle) {
            handleTitleDisplay();
        }
    }

    componentDidMount() {
        const {resetCalculatedTitle,handleTitleDisplay} = this;
        handleTitleDisplay();
        window.addEventListener("resize", resetCalculatedTitle);
    }

    componentWillUnmount() {
        const {resetCalculatedTitle} = this;
        window.removeEventListener("resize", resetCalculatedTitle);
    }

    render() {
        const {title} = this.props;
        const {calculatedTitle,hiddenText} = this.state;

        return (
            <div id="final_title" ref={this.container}>
                <span className="text" ref={this.text}>
                    {calculatedTitle ? calculatedTitle : title}
                </span>
                <span className="hidden-text" style={{visibility:'hidden',position:'absolute'}} ref={this.hiddenText}>
                    {hiddenText ? hiddenText : title}
                </span>
            </div>
        );
    }

    textEllipsisCenter = (text,trimCounter) => {
        return text.substr(0, (text.length/2)-(trimCounter/2)) + '[...]' + text.substr((text.length/2)+(trimCounter/2), text.length);
    };

    resetCalculatedTitle = () => {
        this.setState({
            calculatedTitle: null,
            hiddenText: null,
            trimCounter: 5
        })
    };

    handleTitleDisplay = () => {
        const {title} = this.props;
        const {trimCounter,hiddenText} = this.state;
        const {textEllipsisCenter} = this;

        const containerLength = this.container.current ? this.container.current.offsetWidth : 0;
        const textHiddenLength = this.hiddenText.current ? this.hiddenText.current.offsetWidth : 0;
        if (textHiddenLength >= containerLength) {
            this.setState({
                hiddenText: textEllipsisCenter(title,trimCounter),
                trimCounter: trimCounter+2
            })
        }else{
            this.setState({
                calculatedTitle: hiddenText,
            })
        }
    }


}

export default Truncate;
